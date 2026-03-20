<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();

            // Snapshot of which template version was used
            $table->string('template_key');
            $table->integer('template_version');

            // Dynamic form data — validated against template schema before saving
            $table->json('data');

            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'submitted', 'reviewed', 'archived'])->default('submitted');
            $table->timestamp('recorded_at')->useCurrent();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'client_id']);
            $table->index(['tenant_id', 'template_key', 'template_version']);
            $table->index(['client_id', 'template_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('records');
    }
};
