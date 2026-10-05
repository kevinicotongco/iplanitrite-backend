<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');
            $table->string('status');
            $table->text('description')->nullable();
            $table->string('payment_method');
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->decimal('amount', 10, 2);
            $table->uuid('proof_document_id')->nullable();
            $table->timestamps(6);
            $table->softDeletes();

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('proof_document_id')->references('id')->on('documents')->nullOnDelete();

            $table->index(['invoice_id', 'status'], 'idx_invoice_payments_invoice_status');
        });

        DB::statement('ALTER TABLE invoice_payments ADD CONSTRAINT chk_invoice_payments_amount_positive CHECK (amount > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
    }
};
