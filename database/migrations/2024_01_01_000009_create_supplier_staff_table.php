<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_staff', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('supplier_id');
            $table->uuid('supplier_role_id');
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

            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->foreign('supplier_role_id')->references('id')->on('supplier_roles');
            $table->foreign('profile_picture')->references('id')->on('documents');
            $table->foreign('address_id')->references('id')->on('addresses');
            $table->foreign('contact_number_id')->references('id')->on('contact_numbers');
            // Self-referencing foreign keys deferred to separate migration
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_staff');
    }
};
