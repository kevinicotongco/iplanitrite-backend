<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_seats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('event_floor_plan_id');
            $table->string('table_id');
            $table->string('seat_id');
            $table->uuid('event_guest_id');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['event_floor_plan_id', 'table_id', 'seat_id']);
            $table->unique(['event_floor_plan_id', 'event_guest_id']);

            $table->foreign('event_floor_plan_id')->references('id')->on('event_floor_plans')->onDelete('cascade');
            $table->foreign('event_guest_id')->references('id')->on('event_guests')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('clients')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_seats');
    }
};
