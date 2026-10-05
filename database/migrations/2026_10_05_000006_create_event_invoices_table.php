<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('event_id');
            $table->uuid('invoice_id');
            $table->timestamps(6);

            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');

            $table->unique('invoice_id', 'uq_event_invoices_invoice');
            $table->index('event_id', 'idx_event_invoices_event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_invoices');
    }
};
