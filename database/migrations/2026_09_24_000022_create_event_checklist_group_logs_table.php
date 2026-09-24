<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_checklist_group_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('audit_by');
            $table->string('audit_type');
            $table->dateTime('audit_date');
            $table->uuid('event_checklist_group_id');
            $table->string('action');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('event_checklist_group_id')->references('id')->on('event_checklist_groups')->onDelete('cascade');

            // Index for efficient queries
            $table->index(['event_checklist_group_id', 'audit_date'], 'idx_ecgl_group_date');
            $table->index('audit_by', 'idx_ecgl_audit_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_checklist_group_logs');
    }
};
