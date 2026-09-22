<?php

use App\Enums\SupplierStatusEnum;
use App\Enums\SupplierSubscriptionTierEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('status')->default(SupplierStatusEnum::Active->value);
            $table->uuid('logo')->nullable();
            $table->text('description');
            $table->uuid('address_id')->nullable();
            $table->uuid('country_id');
            $table->uuid('contact_number_id')->nullable();
            $table->string('subscription_tier');
            $table->string('timezone');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('logo')->references('id')->on('documents');
            $table->foreign('address_id')->references('id')->on('addresses');
            $table->foreign('country_id')->references('id')->on('countries');
            $table->foreign('contact_number_id')->references('id')->on('contact_numbers');
            // Foreign keys to supplier_staff deferred to separate migration
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
