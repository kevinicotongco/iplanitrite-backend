<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->uuid('profile_picture')->nullable();
            $table->uuid('address_id')->nullable();
            $table->uuid('contact_number_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts');
            $table->foreign('profile_picture')->references('id')->on('documents');
            $table->foreign('address_id')->references('id')->on('addresses');
            $table->foreign('contact_number_id')->references('id')->on('contact_numbers');
            // Foreign keys to staff deferred to separate migration
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
