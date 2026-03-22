<?php

namespace App\Http\Controllers;

use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientController extends Controller
{
    /**
     * GET /api/clients
     * Query params: ?search=, ?status=active, ?per_page=15, ?page=1
     */
    public function index(Request $request): AnonymousResourceCollection
    {
//        $this->authorize('view_clients'); // Uses Spatie Gate

        $clients = Client::where('tenant_id', $request->user()->tenant_id)
            ->when($request->search, fn ($q) => $q->search($request->search))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return ClientResource::collection($clients);
    }

    /**
     * POST /api/clients
     * Authorization handled in StoreClientRequest::authorize()
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = Client::create([
            ...$request->validated(),
            'tenant_id'  => $request->user()->tenant_id,
            'created_by' => $request->user()->id,
        ]);

        \App\Models\ActivityLog::log(
            $request->user()->tenant_id,
            $request->user()->id,
            'client_created',
            "Created new client: {$client->full_name}",
            "/clients/{$client->id}"
        );

        \App\Models\UserNotification::createForAdmins(
            $request->user()->tenant_id,
            'client_created',
            'New client added',
            "{$request->user()->name} registered a new client: {$client->full_name}.",
            "/clients/{$client->id}"
        );

        return response()->json([
            'message' => 'Client created successfully.',
            'data'    => new ClientResource($client),
        ], 201);
    }

    /**
     * GET /api/clients/{client}
     */
    public function show(Request $request, Client $client): JsonResponse
    {
        $this->authorizeTenant($request, $client);

        return response()->json([
            'data' => new ClientResource($client->load(['records', 'creator'])),
        ]);
    }

    /**
     * PUT/PATCH /api/clients/{client}
     */
    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        $this->authorizeTenant($request, $client);

        $client->update($request->validated());

        \App\Models\ActivityLog::log(
            $request->user()->tenant_id,
            $request->user()->id,
            'client_updated',
            "Updated {$client->full_name}'s profile",
            "/clients/{$client->id}"
        );


        return response()->json([
            'message' => 'Client updated successfully.',
            'data'    => new ClientResource($client->fresh()),
        ]);
    }

    /**
     * DELETE /api/clients/{client}
     */
    public function destroy(Request $request, Client $client): JsonResponse
    {
        $this->authorizeTenant($request, $client);

        if (! $request->user()->can('delete_clients')) {
            abort(403, 'You do not have permission to delete clients.');
        }

        $client->delete();

        return response()->json(['message' => 'Client deleted successfully.']);
    }

    // ─── Tenant isolation guard ───────────────────────────────────────────────

    private function authorizeTenant(Request $request, Client $client): void
    {
        if ($client->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
