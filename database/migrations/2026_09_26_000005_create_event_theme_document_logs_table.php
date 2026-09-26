<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_theme_document_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('event_theme_document_id');
            $table->uuid('audit_by');
            $table->string('audit_type');
            $table->string('action');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('event_theme_document_id', 'etd_logs_etd_id_fk')
                ->references('id')
                ->on('event_theme_documents')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_theme_document_logs');
    }
};
