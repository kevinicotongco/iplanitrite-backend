<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_theme_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('event_theme_document_group_id');
            $table->uuid('document_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('event_theme_document_group_id', 'etd_etdg_id_fk')
                ->references('id')
                ->on('event_theme_document_groups')
                ->onDelete('cascade');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_theme_documents');
    }
};
