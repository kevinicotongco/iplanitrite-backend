<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('iso2_code', 2);
            $table->string('iso3_code', 3);
            $table->string('language_locale');
            $table->string('calling_code');
            $table->string('flag');
            $table->string('currency_code', 3);
            $table->string('currency_name');
            $table->string('currency_symbol');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
