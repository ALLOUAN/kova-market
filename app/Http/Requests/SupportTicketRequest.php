<?php

namespace App\Http\Requests;

use App\Enums\ContactSubject;
use App\Models\SupportTicketMessage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/**
 * A new assistance request from the customer area: a subject, a short title, the message, optionally the customer's
 * own order it is about and up to three files (photos or PDF).
 */
class SupportTicketRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', Rule::enum(ContactSubject::class)->except([ContactSubject::Partnership])],
            'title' => ['required', 'string', 'min:4', 'max:120'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'order' => ['nullable', 'string', Rule::exists('orders', 'number')->where('user_id', $this->user()->getKey())],
            ...self::attachmentRules(),
        ];
    }

    /**
     * Files joined to a message, for a new request and for a reply.
     *
     * @return array<string, mixed>
     */
    public static function attachmentRules(): array
    {
        return [
            'attachments' => ['nullable', 'array', 'max:'.SupportTicketMessage::MAX_ATTACHMENTS],
            'attachments.*' => [File::types(SupportTicketMessage::ATTACHMENT_TYPES)->max(SupportTicketMessage::ATTACHMENT_MAX_KB)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['subject' => 'sujet', 'title' => 'titre', 'order' => 'commande', 'attachments' => 'fichiers', 'attachments.*' => 'fichier'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return self::attachmentMessages();
    }

    /**
     * @return array<string, string>
     */
    public static function attachmentMessages(): array
    {
        return [
            'attachments.max' => 'Vous pouvez joindre '.SupportTicketMessage::MAX_ATTACHMENTS.' fichiers au plus.',
            'attachments.*.mimes' => 'Seuls les photos (JPG, PNG, WebP) et les PDF sont acceptés.',
            'attachments.*.max' => 'Chaque fichier doit peser '.(SupportTicketMessage::ATTACHMENT_MAX_KB / 1024).' Mo au plus.',
        ];
    }
}
