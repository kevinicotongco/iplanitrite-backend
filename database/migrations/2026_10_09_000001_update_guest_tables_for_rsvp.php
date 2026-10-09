<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_guest_groups', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('name');
            $table->dropColumn('event_type');
        });

        Schema::table('event_guests', function (Blueprint $table) {
            $table->dropForeign(['profile_picture']);
            $table->dropForeign(['address_id']);
            $table->dropForeign(['contact_number_id']);
            $table->dropColumn(['profile_picture', 'address_id', 'contact_number_id']);

            $table->integer('sort_order')->default(0)->after('last_name');
            $table->string('status_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('event_guests', function (Blueprint $table) {
            $table->dropColumn(['sort_order', 'status_reason']);

            $table->uuid('profile_picture')->nullable()->after('last_name');
            $table->uuid('address_id')->nullable()->after('profile_picture');
            $table->uuid('contact_number_id')->nullable()->after('address_id');

            $table->foreign('profile_picture')->references('id')->on('documents')->onDelete('set null');
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('set null');
            $table->foreign('contact_number_id')->references('id')->on('contact_numbers')->onDelete('set null');
        });

        Schema::table('event_guest_groups', function (Blueprint $table) {
            $table->dropColumn('sort_order');
            $table->string('event_type')->default('')->after('name');
        });
    }
};
