<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_checklists', function (Blueprint $table) {
            $table->integer('sort_order')->after('status');
            $table->uuid('supplier_id')->nullable()->after('sort_order');
            $table->string('responsibility_type')->nullable()->after('supplier_id');

            // Add foreign key
            $table->foreign('supplier_id')->references('id')->on('suppliers');
        });
    }

    public function down(): void
    {
        Schema::table('event_checklists', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['sort_order', 'supplier_id', 'responsibility_type']);
        });
    }
};
