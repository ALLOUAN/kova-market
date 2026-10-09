<?php

namespace App\Mail;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Services\Storefront\StoreSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/**
 * A newsletter campaign as received by one subscriber: its own newsletter layout (mail.newsletter.campaign: logo,
 * cover, title, content, button, the store's promises, night-blue footer with the contacts), a plain-text copy,
 * and the unsubscribe link in the text and in the List-Unsubscribe header (the "unsubscribe" button of Gmail,
 * Outlook). Logo and cover travel inside the e-mail (embedded), so they show whatever server sends it.
 * A test send has no subscriber: its unsubscribe link only shows where the real one will be.
 */
class NewsletterCampaignMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /** Styles of the content written in the back-office, put inline (mail apps drop <style> blocks). */
    private const CONTENT_CSS = <<<'CSS'
        p { margin: 0 0 16px; font-size: 16px; line-height: 1.65; color: #3f4a5a; }
        h2 { margin: 28px 0 10px; font-size: 21px; line-height: 1.3; font-weight: 700; color: #021732; }
        h3 { margin: 22px 0 8px; font-size: 17px; line-height: 1.35; font-weight: 700; color: #021732; }
        a { color: #cc4400; font-weight: 600; text-decoration: underline; }
        strong, b { color: #021732; }
        ul, ol { margin: 0 0 16px; padding: 0 0 0 22px; color: #3f4a5a; font-size: 16px; line-height: 1.65; }
        li { margin: 0 0 6px; }
        blockquote { margin: 0 0 16px; padding: 12px 16px; border-left: 4px solid #cc4400; background: #fff4ec; color: #021732; font-style: italic; }
        CSS;

    public function __construct(
        public readonly NewsletterCampaign $campaign,
        public readonly ?NewsletterSubscriber $subscriber = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->subscriber ? '' : '[TEST] ').$this->campaign->subject);
    }

    public function headers(): Headers
    {
        return new Headers(text: ['List-Unsubscribe' => '<'.$this->unsubscribeUrl().'>']);
    }

    public function content(): Content
    {
        $contact = app(StoreSettings::class)->contact();

        return new Content(
            view: 'mail.newsletter.campaign',
            text: 'mail.newsletter.campaign-text',
            with: [
                'campaign' => $this->campaign,
                'body' => $this->styledContent(),
                'unsubscribeUrl' => $this->unsubscribeUrl(),
                'isTest' => $this->subscriber === null,
                'contact' => $contact,
                'whatsappUrl' => app(StoreSettings::class)->whatsappUrl(),
                'logoPath' => public_path('assets/images/logo/kova-logo.png'),
                'coverPath' => $this->campaign->image ? public_path($this->campaign->image) : null,
            ],
        );
    }

    /**
     * The content of the back-office with its styles inline.
     */
    private function styledContent(): string
    {
        $html = '<div id="kova-content">'.$this->campaign->content.'</div>';
        $inlined = (new CssToInlineStyles)->convert($html, self::CONTENT_CSS);

        // The inliner returns a whole document: keep the content only.
        return preg_match('~<div id="kova-content">(.*)</div>~s', $inlined, $match) ? $match[1] : $this->campaign->content;
    }

    private function unsubscribeUrl(): string
    {
        return $this->subscriber ? route('newsletter.unsubscribe', $this->subscriber) : url('/');
    }
}
