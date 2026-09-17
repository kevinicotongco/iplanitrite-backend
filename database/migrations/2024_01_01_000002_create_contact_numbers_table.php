<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_numbers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number');
            $table->uuid('country_id');

            $table->foreign('country_id')->references('id')->on('countries')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_numbers');
    }
};
