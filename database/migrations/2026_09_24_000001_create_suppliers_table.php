<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->string('company_name');
            $table->string('contact_person');
            $table->uuid('contact_number_id');
            $table->uuid('address_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts');
            $table->foreign('contact_number_id')->references('id')->on('contact_numbers');
            $table->foreign('address_id')->references('id')->on('addresses');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
