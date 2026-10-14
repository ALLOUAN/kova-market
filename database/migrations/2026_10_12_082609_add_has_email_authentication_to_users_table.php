<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Back-office two-factor by e-mail: the 6-digit sign-in code is sent to the account's address (AdminLoginCode),
     * as an alternative to the authenticator app.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('has_email_authentication')->default(false)->after('app_authentication_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('has_email_authentication');
        });
    }
};
