<?php

namespace App\Services;

use App\Models\Template;
use Illuminate\Support\Collection;

/**
 * Centralises all template resolution logic.
 * Inject this into controllers or jobs that need template operations.
 */
class TemplateService
{
    /**
     * Resolve the latest active template for a given tenant + key.
     */
    public function resolve(int $tenantId, string $key): ?Template
    {
        return Template::where('tenant_id', $tenantId)
            ->where('key', $key)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();
    }

    /**
     * Resolve a specific version — used when re-rendering historical records.
     */
    public function resolveVersion(int $tenantId, string $key, int $version): ?Template
    {
        return Template::where('tenant_id', $tenantId)
            ->where('key', $key)
            ->where('version', $version)
            ->first();
    }

    /**
     * Latest active version per key — for the index endpoint.
     *
     * @return Collection<int, Template>
     */
    public function latestByKey(int $tenantId): Collection
    {
        return Template::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->sortByDesc('version')
            ->unique('key')
            ->values();
    }

    /**
     * All versions of a single key — for a version history screen.
     *
     * @return Collection<int, Template>
     */
    public function allVersions(int $tenantId, string $key): Collection
    {
        return Template::where('tenant_id', $tenantId)
            ->where('key', $key)
            ->orderByDesc('version')
            ->get();
    }

    /**
     * Validate data against a template's schema.
     * Returns errors array (empty = valid).
     */
    public function validateData(Template $template, array $data): array
    {
        return $template->validateData($data);
    }

    /**
     * Calculate the next version number for a given tenant + key.
     */
    public function nextVersion(int $tenantId, string $key): int
    {
        $latest = Template::where('tenant_id', $tenantId)
            ->where('key', $key)
            ->max('version');

        return ($latest ?? 0) + 1;
    }
}
