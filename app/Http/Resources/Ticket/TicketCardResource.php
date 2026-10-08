<?php

namespace App\Http\Resources\Ticket;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * The compact shape a board card needs — no full description, no comments.
 *
 * @mixin Ticket
 */
class TicketCardResource extends JsonResource
{
    /** Characters of the description a card shows. */
    public const EXCERPT_LENGTH = 140;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cover = $this->attachments->first(fn ($attachment): bool => $attachment->isImage());

        return [
            'id' => $this->id,
            'title' => $this->title,
            'excerpt' => Str::limit((string) ($this->excerpt ?? $this->description), self::EXCERPT_LENGTH),
            'status' => $this->status,
            'group' => $this->group,
            'comments_count' => (int) $this->comments_count,
            'attachments_count' => (int) $this->attachments_count,
            'cover' => $cover ? '/storage/'.ltrim($cover->file_path, '/') : null,
            'creator' => $this->creator?->name,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
