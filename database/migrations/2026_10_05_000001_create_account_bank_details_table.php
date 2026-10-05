<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_bank_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->string('bank_name');
            $table->string('account_number');
            $table->text('qr_code')->nullable();
            $table->uuid('created_by');
            $table->uuid('updated_by');
            $table->timestamps(6);
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts');
            $table->foreign('created_by')->references('id')->on('staff');
            $table->foreign('updated_by')->references('id')->on('staff');

            $table->index('account_id', 'idx_abd_account');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_bank_details');
    }
};
