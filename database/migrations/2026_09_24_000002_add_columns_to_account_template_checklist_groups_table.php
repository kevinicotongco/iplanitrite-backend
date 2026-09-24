<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_template_checklist_groups', function (Blueprint $table) {
            $table->string('checklist_type')->after('event_type');
            $table->integer('sort_order')->after('checklist_type');

            // Add composite index for performance
            $table->index(['account_id', 'checklist_type', 'event_type', 'sort_order'], 'idx_atcg_account_type_event_sort');
        });
    }

    public function down(): void
    {
        Schema::table('account_template_checklist_groups', function (Blueprint $table) {
            $table->dropIndex('idx_atcg_account_type_event_sort');
            $table->dropColumn(['checklist_type', 'sort_order']);
        });
    }
};
