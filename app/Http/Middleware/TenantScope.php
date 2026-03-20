<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves and validates the tenant from the authenticated user.
 * Injects the Tenant model onto $request->attributes for use in controllers.
 *
 * IMPORTANT: tenant_id is ALWAYS sourced from Auth::user(), never from the
 * request body, to prevent cross-tenant data access.
 *
 * Usage: Route::middleware(['auth:sanctum', 'tenant.scope'])->group(...)
 */
class TenantScope
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user || ! $user->tenant_id) {
            return response()->json([
                'message' => 'Tenant context could not be resolved.',
            ], 403);
        }

        $tenant = $user->tenant;

        if (! $tenant || ! $tenant->is_active) {
            return response()->json([
                'message' => 'Your organisation account is inactive. Please contact support.',
            ], 403);
        }

        // Make tenant accessible on the request for controllers that need it
        $request->merge(['_tenant_id' => $user->tenant_id]);
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
