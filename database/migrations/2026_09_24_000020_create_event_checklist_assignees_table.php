<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_checklist_assignees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assignee_id');
            $table->string('assignee_type');
            $table->uuid('event_checklist_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('event_checklist_id')->references('id')->on('event_checklists')->onDelete('cascade');

            // Index for efficient lookups
            $table->index(['event_checklist_id', 'assignee_type'], 'idx_eca_checklist_type');
            $table->index(['assignee_id', 'assignee_type'], 'idx_eca_assignee');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_checklist_assignees');
    }
};
