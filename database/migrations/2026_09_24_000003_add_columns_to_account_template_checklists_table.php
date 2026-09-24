<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_template_checklists', function (Blueprint $table) {
            // Rename frequency_days to frequency_value
            $table->renameColumn('frequency_days', 'frequency_value');
        });

        Schema::table('account_template_checklists', function (Blueprint $table) {
            // Add new columns
            $table->integer('sort_order')->after('frequency_value');
            $table->uuid('supplier_id')->nullable()->after('sort_order');
            $table->string('responsibility_type')->nullable()->after('supplier_id');

            // Make frequency fields nullable if not already
            $table->integer('frequency_value')->nullable()->change();
            $table->string('frequency_type')->nullable()->change();
            $table->string('frequency_anchor')->nullable()->change();

            // Add foreign key
            $table->foreign('supplier_id')->references('id')->on('suppliers');

            // Add index for performance
            $table->index(['account_template_checklist_group_id', 'sort_order'], 'idx_atc_group_sort');
        });
    }

    public function down(): void
    {
        Schema::table('account_template_checklists', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropIndex('idx_atc_group_sort');
            $table->dropColumn(['sort_order', 'supplier_id', 'responsibility_type']);
        });

        Schema::table('account_template_checklists', function (Blueprint $table) {
            $table->renameColumn('frequency_value', 'frequency_days');
        });
    }
};
