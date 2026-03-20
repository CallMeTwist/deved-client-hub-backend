<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'domain', 'clinic_type', 'settings', 'is_active',
    ];

    protected $casts = [
        'settings'  => 'array',
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(Record::class);
    }

    public function latestTemplate(string $key): ?Template
    {
        return $this->templates()
            ->where('key', $key)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();
    }
}
