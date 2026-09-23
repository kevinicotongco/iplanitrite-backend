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
            $table->uuid('account_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default(EventStatusEnum::Pending->value);
            $table->string('event_type');
            $table->uuid('celebrant_one_id');
            $table->uuid('celebrant_two_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts');
            $table->foreign('celebrant_one_id')->references('id')->on('celebrants');
            $table->foreign('celebrant_two_id')->references('id')->on('celebrants');
            // Foreign keys to staff deferred to separate migration
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
