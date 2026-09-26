<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->uuid('thumbnail_id')->nullable()->after('celebrant_two_id');
            $table->string('dress_code')->nullable()->after('thumbnail_id');
            $table->string('theme')->nullable()->after('dress_code');

            $table->foreign('thumbnail_id')->references('id')->on('documents')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['thumbnail_id']);
            $table->dropColumn(['thumbnail_id', 'dress_code', 'theme']);
        });
    }
};
