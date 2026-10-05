<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['staff', 'clients'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['profile_picture']);
                $table->dropColumn('profile_picture');
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->text('profile_picture')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['staff', 'clients'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('profile_picture');
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->uuid('profile_picture')->nullable();
                $table->foreign('profile_picture')->references('id')->on('documents');
            });
        }
    }
};
