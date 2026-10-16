<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Database\Factories\SupportTicketMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * One message of an assistance request, from the customer or from the team ("from_staff"), with its files (photos,
 * receipts). The files live on the private disk: only the customer of the request and the team open them, through
 * account.support.attachment.
 */
#[Fillable(['support_ticket_id', 'user_id', 'from_staff', 'body', 'attachments'])]
class SupportTicketMessage extends Model
{
    /** @use HasFactory<SupportTicketMessageFactory> */
    use HasFactory, HasPublicId;

    public const DISK = 'local';

    /** Files per message, and the types and size (kilobytes) accepted. */
    public const MAX_ATTACHMENTS = 3;

    public const ATTACHMENT_TYPES = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

    public const ATTACHMENT_MAX_KB = 5120;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_staff' => 'boolean',
            'attachments' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    /**
     * Stores uploaded files for a request, as kept in "attachments".
     *
     * @param  array<int, UploadedFile>  $files
     * @return list<array{path: string, name: string, size: int}>
     */
    public static function storeAttachments(SupportTicket $ticket, array $files): array
    {
        return array_values(array_map(fn (UploadedFile $file) => [
            'path' => $file->store("support/{$ticket->public_id}", self::DISK),
            'name' => Str::limit($file->getClientOriginalName(), 100, ''),
            'size' => (int) $file->getSize(),
        ], $files));
    }

    /**
     * The files of the message, each with whether it shows as a picture.
     *
     * @return list<array{index: int, name: string, size: int, image: bool}>
     */
    public function files(): array
    {
        return array_map(fn (array $file, int $index) => [
            'index' => $index,
            'name' => $file['name'],
            'size' => $file['size'],
            'image' => ! Str::endsWith(Str::lower($file['path']), '.pdf'),
        ], $this->attachments ?? [], array_keys($this->attachments ?? []));
    }
}
