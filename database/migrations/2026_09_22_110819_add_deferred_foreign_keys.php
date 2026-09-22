<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add foreign keys from suppliers to supplier_staff
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('supplier_staff');
            $table->foreign('updated_by')->references('id')->on('supplier_staff');
        });

        // Add foreign keys from supplier_roles to supplier_staff
        Schema::table('supplier_roles', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('supplier_staff');
            $table->foreign('updated_by')->references('id')->on('supplier_staff');
        });

        // Add foreign keys from supplier_role_permissions to supplier_staff
        Schema::table('supplier_role_permissions', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('supplier_staff');
            $table->foreign('updated_by')->references('id')->on('supplier_staff');
        });

        // Add self-referencing foreign keys to supplier_staff
        Schema::table('supplier_staff', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('supplier_staff');
            $table->foreign('updated_by')->references('id')->on('supplier_staff');
        });

        // Add foreign keys from supplier_template_checklist_groups to supplier_staff
        Schema::table('supplier_template_checklist_groups', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('supplier_staff');
            $table->foreign('updated_by')->references('id')->on('supplier_staff');
        });

        // Add foreign keys from supplier_template_checklists to supplier_staff
        Schema::table('supplier_template_checklists', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('supplier_staff');
            $table->foreign('updated_by')->references('id')->on('supplier_staff');
        });

        // Add foreign keys from clients to supplier_staff
        Schema::table('clients', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('supplier_staff');
            $table->foreign('updated_by')->references('id')->on('supplier_staff');
        });

        // Add foreign keys from celebrants to supplier_staff
        Schema::table('celebrants', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('supplier_staff');
            $table->foreign('updated_by')->references('id')->on('supplier_staff');
        });

        // Add foreign keys from events to supplier_staff
        Schema::table('events', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('supplier_staff');
            $table->foreign('updated_by')->references('id')->on('supplier_staff');
        });

        // Add foreign keys from event_clients to supplier_staff
        Schema::table('event_clients', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('supplier_staff');
            $table->foreign('updated_by')->references('id')->on('supplier_staff');
        });

        // Add foreign keys from event_checklist_groups to supplier_staff
        Schema::table('event_checklist_groups', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('supplier_staff');
            $table->foreign('updated_by')->references('id')->on('supplier_staff');
        });

        // Add foreign keys from event_checklists to supplier_staff
        Schema::table('event_checklists', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('supplier_staff');
            $table->foreign('updated_by')->references('id')->on('supplier_staff');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys in reverse order
        Schema::table('event_checklists', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::table('event_checklist_groups', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::table('event_clients', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::table('celebrants', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::table('supplier_template_checklists', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::table('supplier_template_checklist_groups', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::table('supplier_staff', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::table('supplier_role_permissions', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::table('supplier_roles', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });
    }
};
