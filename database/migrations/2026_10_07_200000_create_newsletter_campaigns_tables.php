<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Newsletter campaigns sent by e-mail to the subscribers:
     *  - newsletter_campaigns: the message (subject, preview line, content, optional button), its date and counters;
     *  - newsletter_campaign_recipients: the subscribers frozen at launch, one row each, so a retried batch never
     *    sends twice and the counters stay exact.
     */
    public function up(): void
    {
        Schema::create('newsletter_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('subject', 150);
            $table->string('preheader', 150)->nullable();
            // Cover picture, JPEG or PNG (WebP is not shown by Outlook), path on the storefront disk.
            $table->string('image')->nullable();
            $table->longText('content');
            $table->string('button_label', 60)->nullable();
            $table->string('button_url', 500)->nullable();
            $table->string('status', 20)->default('brouillon')->index();
            $table->dateTime('scheduled_at')->nullable()->index();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('newsletter_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('newsletter_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('newsletter_subscriber_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('en_attente');
            $table->dateTime('sent_at')->nullable();
            $table->string('error', 255)->nullable();
            $table->timestamps();

            $table->unique(['newsletter_campaign_id', 'newsletter_subscriber_id'], 'campaign_recipient_unique');
            $table->index(['newsletter_campaign_id', 'status'], 'campaign_recipient_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_campaign_recipients');
        Schema::dropIfExists('newsletter_campaigns');
    }
};
