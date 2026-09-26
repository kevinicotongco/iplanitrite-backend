<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add unique partial index for PostgreSQL
        DB::statement('CREATE UNIQUE INDEX event_segments_event_id_is_primary_unique ON event_segments (event_id) WHERE is_primary = true');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS event_segments_event_id_is_primary_unique');
    }
};
