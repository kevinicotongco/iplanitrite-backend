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
        Schema::table('supplier_template_checklists', function (Blueprint $table) {
            // Frequency in days relative to event date
            // Positive = days after event, Negative = days before event
            $table->integer('frequency_days')->default(0)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_template_checklists', function (Blueprint $table) {
            $table->dropColumn('frequency_days');
        });
    }
};
