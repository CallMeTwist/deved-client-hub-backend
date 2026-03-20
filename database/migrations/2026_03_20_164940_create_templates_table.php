<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');                // "Physiotherapy Assessment"
            $table->string('key');                 // "physio_assessment" — stable machine key
            $table->integer('version')->default(1);
            $table->text('description')->nullable();

            // JSON Schema — each field: { name, label, type, required, options, validation }
            $table->json('schema');

            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'key', 'version']);
            $table->index(['tenant_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
