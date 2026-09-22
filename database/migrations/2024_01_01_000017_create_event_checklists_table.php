<?php

use App\Enums\EventChecklistStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_checklists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('event_checklist_group_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default(EventChecklistStatusEnum::Pending->value);
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('event_checklist_group_id')->references('id')->on('event_checklist_groups');
            $table->foreign('created_by')->references('id')->on('supplier_staff');
            $table->foreign('updated_by')->references('id')->on('supplier_staff');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_checklists');
    }
};
