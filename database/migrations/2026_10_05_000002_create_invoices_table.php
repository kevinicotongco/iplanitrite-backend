<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->string('invoice_number');
            $table->string('status');
            $table->text('cancellation_notes')->nullable();
            $table->date('due_date');
            $table->uuid('created_by');
            $table->uuid('updated_by');
            $table->timestamps(6);
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts');
            $table->foreign('created_by')->references('id')->on('staff');
            $table->foreign('updated_by')->references('id')->on('staff');

            $table->unique(['account_id', 'invoice_number'], 'uq_invoices_account_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
