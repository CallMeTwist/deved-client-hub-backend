<?php

namespace App\Http\Controllers;

use App\Http\Requests\Template\StoreTemplateRequest;
use App\Http\Requests\Template\UpdateTemplateRequest;
use App\Http\Resources\TemplateResource;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TemplateController extends Controller
{
    /**
     * GET /api/templates
     *
     * Returns the latest active version of every template key for this tenant.
     * React consumes template.schema.fields to render DynamicForm inputs.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenantId = $request->user()->tenant_id;

        // One subquery gets the MAX(id) per key — acts as "latest version" proxy
        $templates = Template::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereIn('id', function ($query) use ($tenantId) {
                $query->selectRaw('MAX(id)')
                    ->from('templates')
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->groupBy('key');
            })
            ->orderBy('name')
            ->get();

        return TemplateResource::collection($templates);
    }

    /**
     * GET /api/templates/{template}
     */
    public function show(Request $request, Template $template): JsonResponse
    {
        $this->authorizeTenant($request, $template);

        return response()->json(['data' => new TemplateResource($template)]);
    }

    /**
     * POST /api/templates
     *
     * POSTing with an existing key auto-increments the version.
     * Old versions are preserved — records that used them remain valid.
     */
    public function store(StoreTemplateRequest $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $latestVersion = Template::where('tenant_id', $tenantId)
                ->where('key', $request->key)
                ->max('version') ?? 0;

        $template = Template::create([
            ...$request->validated(),
            'tenant_id'  => $tenantId,
            'version'    => $latestVersion + 1,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Template created successfully.',
            'data'    => new TemplateResource($template),
        ], 201);
    }

    /**
     * PUT /api/templates/{template}
     *
     * Only name, description, and is_active are mutable after creation.
     * Schema changes require a new POST (creating a new version).
     */
    public function update(UpdateTemplateRequest $request, Template $template): JsonResponse
    {
        $this->authorizeTenant($request, $template);

        $template->update($request->validated());

        return response()->json([
            'message' => 'Template updated.',
            'data'    => new TemplateResource($template->fresh()),
        ]);
    }

    // ─── Tenant isolation guard ───────────────────────────────────────────────

    private function authorizeTenant(Request $request, Template $template): void
    {
        if ($template->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
