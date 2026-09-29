<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->uuid('event_package_id');

            $table->foreign('event_package_id')->references('id')->on('event_packages');
            $table->index('event_package_id', 'idx_events_event_package_id');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['event_package_id']);
            $table->dropIndex('idx_events_event_package_id');
            $table->dropColumn('event_package_id');
        });
    }
};
