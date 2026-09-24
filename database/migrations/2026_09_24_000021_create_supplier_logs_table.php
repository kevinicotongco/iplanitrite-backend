<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('audit_by');
            $table->string('audit_type');
            $table->dateTime('audit_date');
            $table->uuid('supplier_id');
            $table->string('action');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');

            // Index for efficient queries
            $table->index(['supplier_id', 'audit_date'], 'idx_sl_supplier_date');
            $table->index('audit_by', 'idx_sl_audit_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_logs');
    }
};
