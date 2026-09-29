<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Newsletter sign-ups (footer form, invitation window): the e-mail, where it was given, and the dates of the
        // consent and of its withdrawal. The token is the one-click unsubscribe link of every e-mail.
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('source', 20);
            $table->char('token', 40)->unique();
            $table->timestamp('subscribed_at');
            $table->timestamp('unsubscribed_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
