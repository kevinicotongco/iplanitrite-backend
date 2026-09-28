<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_checklist_groups', function (Blueprint $table) {
            $table->string('checklist_type')->after('event_type');

            $table->index(['event_id', 'checklist_type', 'sort_order'], 'idx_ecg_event_type_sort');
        });
    }

    public function down(): void
    {
        Schema::table('event_checklist_groups', function (Blueprint $table) {
            $table->dropIndex('idx_ecg_event_type_sort');
            $table->dropColumn('checklist_type');
        });
    }
};
