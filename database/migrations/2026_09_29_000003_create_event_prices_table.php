<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->uuid('event_id');
            $table->string('name');
            $table->decimal('cost_price', 10, 2);
            $table->decimal('retail_price', 10, 2);
            $table->integer('sort_order');
            $table->uuid('supplier_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps(6);

            $table->foreign('account_id')->references('id')->on('accounts');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('staff');
            $table->foreign('updated_by')->references('id')->on('staff');

            $table->index(['event_id', 'sort_order'], 'idx_eprice_event_sort');
            $table->index(['account_id', 'event_id'], 'idx_eprice_account_event');
        });

        DB::statement('ALTER TABLE event_prices ADD CONSTRAINT chk_event_prices_sort_order_non_negative CHECK (sort_order >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('event_prices');
    }
};
