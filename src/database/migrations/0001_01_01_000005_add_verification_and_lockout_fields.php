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
        $prefix = config('swift-auth.table_prefix', 'swift-auth_');

        Schema::table($prefix . 'Users', function (Blueprint $table) {
            $table->string('email_verification_token')->nullable()->after('email_verified_at');
            $table->timestamp('email_verification_sent_at')->nullable()->after('email_verification_token');
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('email_verification_sent_at');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->timestamp('last_failed_login_at')->nullable()->after('locked_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = config('swift-auth.table_prefix', 'swift-auth_');

        Schema::table($prefix . 'Users', function (Blueprint $table) {
            $table->dropColumn([
                'email_verification_token',
                'email_verification_sent_at',
                'failed_login_attempts',
                'locked_until',
                'last_failed_login_at',
            ]);
        });
    }
};
