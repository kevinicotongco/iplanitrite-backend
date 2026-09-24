<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('event_checklist_groups', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('event_checklists', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_checklist_groups', function (Blueprint $table) {
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
        });

        Schema::table('event_checklists', function (Blueprint $table) {
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
        });
    }
};
