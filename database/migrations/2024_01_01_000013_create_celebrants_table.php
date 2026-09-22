<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('celebrants', function (Blueprint $table) {
            $table->uuid('id')->primary();
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

            $table->foreign('profile_picture')->references('id')->on('documents');
            $table->foreign('address_id')->references('id')->on('addresses');
            $table->foreign('contact_number_id')->references('id')->on('contact_numbers');
            $table->foreign('created_by')->references('id')->on('supplier_staff');
            $table->foreign('updated_by')->references('id')->on('supplier_staff');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('celebrants');
    }
};
