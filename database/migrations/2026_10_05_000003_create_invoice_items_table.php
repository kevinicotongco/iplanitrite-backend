<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->uuid('created_by');
            $table->uuid('updated_by');
            $table->timestamps(6);
            $table->softDeletes();

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('staff');
            $table->foreign('updated_by')->references('id')->on('staff');

            $table->index('invoice_id', 'idx_invoice_items_invoice');
        });

        DB::statement('ALTER TABLE invoice_items ADD CONSTRAINT chk_invoice_items_amount_positive CHECK (amount > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
