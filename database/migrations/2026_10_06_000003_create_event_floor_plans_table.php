<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_floor_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('event_id')->index();
            $table->json('canvas_state');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('staff')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('staff')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_floor_plans');
    }
};
