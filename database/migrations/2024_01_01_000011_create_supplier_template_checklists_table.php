<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_template_checklists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('supplier_template_checklist_group_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('supplier_template_checklist_group_id')->references('id')->on('supplier_template_checklist_groups');
            // Foreign keys to supplier_staff deferred to separate migration
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_template_checklists');
    }
};
