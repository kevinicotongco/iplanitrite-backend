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
        // Add foreign keys from accounts to staff
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('staff');
            $table->foreign('updated_by')->references('id')->on('staff');
        });

        // Add foreign keys from account_roles to staff
        Schema::table('account_roles', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('staff');
            $table->foreign('updated_by')->references('id')->on('staff');
        });

        // Add foreign keys from account_role_permissions to staff
        Schema::table('account_role_permissions', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('staff');
            $table->foreign('updated_by')->references('id')->on('staff');
        });

        // Add self-referencing foreign keys to staff
        Schema::table('staff', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('staff');
            $table->foreign('updated_by')->references('id')->on('staff');
        });

        // Add foreign keys from account_template_checklist_groups to staff
        Schema::table('account_template_checklist_groups', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('staff');
            $table->foreign('updated_by')->references('id')->on('staff');
        });

        // Add foreign keys from account_template_checklists to staff
        Schema::table('account_template_checklists', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('staff');
            $table->foreign('updated_by')->references('id')->on('staff');
        });

        // Add foreign keys from clients to staff
        Schema::table('clients', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('staff');
            $table->foreign('updated_by')->references('id')->on('staff');
        });

        // Add foreign keys from celebrants to staff
        Schema::table('celebrants', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('staff');
            $table->foreign('updated_by')->references('id')->on('staff');
        });

        // Add foreign keys from events to staff
        Schema::table('events', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('staff');
            $table->foreign('updated_by')->references('id')->on('staff');
        });

        // Add foreign keys from event_clients to staff
        Schema::table('event_clients', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('staff');
            $table->foreign('updated_by')->references('id')->on('staff');
        });

        // Add foreign keys from event_checklist_groups to staff
        Schema::table('event_checklist_groups', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('staff');
            $table->foreign('updated_by')->references('id')->on('staff');
        });

        // Add foreign keys from event_checklists to staff
        Schema::table('event_checklists', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('staff');
            $table->foreign('updated_by')->references('id')->on('staff');
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

        Schema::table('account_template_checklists', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::table('account_template_checklist_groups', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::table('account_role_permissions', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::table('account_roles', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });
    }
};
