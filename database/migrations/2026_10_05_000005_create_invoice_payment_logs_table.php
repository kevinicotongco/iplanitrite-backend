<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_payment_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_payment_id');
            $table->uuid('audit_by');
            $table->string('audit_type');
            $table->string('action');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('invoice_payment_id', 'ipl_invoice_payment_id_fk')
                ->references('id')
                ->on('invoice_payments')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payment_logs');
    }
};
