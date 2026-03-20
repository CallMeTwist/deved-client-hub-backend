<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Template extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name', 'key', 'version', 'description', 'schema', 'is_active', 'created_by',
    ];

    protected $casts = [
        'schema'    => 'array',
        'is_active' => 'boolean',
        'version'   => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function records(): HasMany
    {
        return $this->hasMany(Record::class, 'template_key', 'key')
            ->where('template_version', $this->version);
    }

    /**
     * Return field definitions from the schema array.
     * Schema shape: { fields: [ { name, label, type, required, options, validation } ] }
     */
    public function fields(): array
    {
        return $this->schema['fields'] ?? [];
    }

    /**
     * Validate a data payload against this template's schema.
     * Returns an array of validation errors — empty means valid.
     * Called by RecordController before saving any Record.
     */
    public function validateData(array $data): array
    {
        $errors = [];

        foreach ($this->fields() as $field) {
            $name     = $field['name']     ?? null;
            $required = $field['required'] ?? false;
            $type     = $field['type']     ?? 'text';

            if (! $name) {
                continue;
            }

            $value = $data[$name] ?? null;

            if ($required && ($value === null || $value === '')) {
                $errors[$name][] = "The {$name} field is required.";
                continue;
            }

            if ($value === null) {
                continue;
            }

            match ($type) {
                'number'  => is_numeric($value) ?: $errors[$name][] = "{$name} must be a number.",
                'boolean' => is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true)
                    ?: $errors[$name][] = "{$name} must be boolean.",
                'select'  => in_array($value, $field['options'] ?? [], true)
                    ?: $errors[$name][] = "{$name} must be one of: " . implode(', ', $field['options'] ?? []) . '.',
                default   => null,
            };

            if ($type === 'number' && isset($field['validation'])) {
                $rules = $field['validation'];
                if (isset($rules['min']) && $value < $rules['min']) {
                    $errors[$name][] = "{$name} must be at least {$rules['min']}.";
                }
                if (isset($rules['max']) && $value > $rules['max']) {
                    $errors[$name][] = "{$name} must not exceed {$rules['max']}.";
                }
            }
        }

        return $errors;
    }
}
