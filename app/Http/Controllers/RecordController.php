<?php

namespace App\Http\Controllers;

use App\Http\Requests\Record\StoreRecordRequest;
use App\Http\Resources\RecordResource;
use App\Models\Client;
use App\Models\Record;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecordController extends Controller
{
    /**
     * GET /api/clients/{client}/records
     * Optional: ?template_key=physio_assessment
     */
    public function index(Request $request, Client $client): AnonymousResourceCollection
    {
        $this->authorizeClientAccess($request, $client);

        $records = Record::where('tenant_id', $request->user()->tenant_id)
            ->where('client_id', $client->id)
            ->when($request->template_key, fn ($q) => $q->where('template_key', $request->template_key))
            ->with(['creator:id,name', 'reviewer:id,name'])
            ->orderByDesc('recorded_at')
            ->paginate($request->integer('per_page', 20));

        return RecordResource::collection($records);
    }

    /**
     * POST /api/clients/{client}/records
     *
     * Flow:
     * 1. Validate the request body (StoreRecordRequest)
     * 2. Resolve the latest active template for the given key
     * 3. Validate `data` against the template schema (Template::validateData)
     * 4. Save the record with a version snapshot
     */
    public function store(StoreRecordRequest $request, Client $client): JsonResponse
    {
        $this->authorizeClientAccess($request, $client);

        $tenantId    = $request->user()->tenant_id;
        $templateKey = $request->template_key;

        $template = Template::where('tenant_id', $tenantId)
            ->where('key', $templateKey)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();

        if (! $template) {
            return response()->json([
                'message' => "No active template found for key: {$templateKey}",
            ], 422);
        }

        // Validate dynamic data against template schema fields
        $validationErrors = $template->validateData($request->data);

        if (! empty($validationErrors)) {
            return response()->json([
                'message' => 'Template data validation failed.',
                'errors'  => $validationErrors,
            ], 422);
        }

        $record = Record::create([
            'tenant_id'        => $tenantId,
            'client_id'        => $client->id,
            'template_key'     => $template->key,
            'template_version' => $template->version,
            'data'             => $request->data,
            'notes'            => $request->notes,
            'status'           => 'submitted',
            'recorded_at'      => $request->recorded_at ?? now(),
            'created_by'       => $request->user()->id,
        ]);

        \App\Models\ActivityLog::log(
            $tenantId,
            $request->user()->id,
            'record_created',
            "Added a " . str_replace('_', ' ', $template->key) . " record for {$client->full_name}",
            "/clients/{$client->id}"
        );

        \App\Models\UserNotification::createForAdmins(
            $tenantId,
            'record_created',
            'New record added',
            "{$request->user()->name} added a " . str_replace('_', ' ', $template->key) . " record for {$client->full_name}.",
            "/clients/{$client->id}"
        );

        return response()->json([
            'message' => 'Record saved successfully.',
            'data'    => new RecordResource($record->load(['creator:id,name'])),
        ], 201);
    }

    /**
     * GET /api/clients/{client}/records/{record}
     */
    public function show(Request $request, Client $client, Record $record): JsonResponse
    {
        $this->authorizeClientAccess($request, $client);
        $this->authorizeRecordAccess($request, $record);

        return response()->json([
            'data' => new RecordResource($record->load(['creator:id,name', 'reviewer:id,name'])),
        ]);
    }

    // ─── Tenant isolation guards ──────────────────────────────────────────────

    private function authorizeClientAccess(Request $request, Client $client): void
    {
        if ($client->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'Access denied.');
        }
    }

    private function authorizeRecordAccess(Request $request, Record $record): void
    {
        if ($record->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
