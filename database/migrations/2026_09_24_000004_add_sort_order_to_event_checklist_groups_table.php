<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_checklist_groups', function (Blueprint $table) {
            $table->integer('sort_order')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('event_checklist_groups', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
