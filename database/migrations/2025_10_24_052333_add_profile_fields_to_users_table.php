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
        Schema::table('users', function (Blueprint $table) {
            // Add new profile fields (skip role as it already exists)
            if (!Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('bio');
            }
            
            // Add notification preferences
            if (!Schema::hasColumn('users', 'email_notifications')) {
                $table->boolean('email_notifications')->default(true)->after('avatar');
            }
            if (!Schema::hasColumn('users', 'order_notifications')) {
                $table->boolean('order_notifications')->default(true)->after('email_notifications');
            }
            if (!Schema::hasColumn('users', 'user_notifications')) {
                $table->boolean('user_notifications')->default(true)->after('order_notifications');
            }
            if (!Schema::hasColumn('users', 'system_notifications')) {
                $table->boolean('system_notifications')->default(false)->after('user_notifications');
            }
            if (!Schema::hasColumn('users', 'marketing_emails')) {
                $table->boolean('marketing_emails')->default(false)->after('system_notifications');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'bio',
                'avatar',
                'email_notifications',
                'order_notifications',
                'user_notifications',
                'system_notifications',
                'marketing_emails'
            ]);
        });
    }
};
