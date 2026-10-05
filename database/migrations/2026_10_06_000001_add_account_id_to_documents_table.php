<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->uuid('account_id')->after('id');

            $table->foreign('account_id')->references('id')->on('accounts');
            $table->index('account_id', 'idx_documents_account');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('idx_documents_account');
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
        });
    }
};
