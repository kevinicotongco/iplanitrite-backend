<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_role_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('supplier_role_id');
            $table->string('permission');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('supplier_role_id')->references('id')->on('supplier_roles');
            $table->foreign('created_by')->references('id')->on('supplier_staff');
            $table->foreign('updated_by')->references('id')->on('supplier_staff');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_role_permissions');
    }
};
