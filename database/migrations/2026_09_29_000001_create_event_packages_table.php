<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->string('name');
            $table->string('event_type');
            $table->decimal('price', 10, 2);
            $table->text('description');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts');
            $table->foreign('created_by')->references('id')->on('staff');
            $table->foreign('updated_by')->references('id')->on('staff');

            $table->index(['account_id', 'event_type', 'name'], 'idx_ep_account_type_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_packages');
    }
};
