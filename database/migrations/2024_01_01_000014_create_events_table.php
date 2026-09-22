<?php

use App\Enums\EventStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('supplier_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default(EventStatusEnum::Pending->value);
            $table->string('event_type');
            $table->dateTime('event_date');
            $table->uuid('celebrant_one_id');
            $table->uuid('celebrant_two_id')->nullable();
            $table->uuid('address_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->foreign('celebrant_one_id')->references('id')->on('celebrants');
            $table->foreign('celebrant_two_id')->references('id')->on('celebrants');
            $table->foreign('address_id')->references('id')->on('addresses');
            $table->foreign('created_by')->references('id')->on('supplier_staff');
            $table->foreign('updated_by')->references('id')->on('supplier_staff');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
