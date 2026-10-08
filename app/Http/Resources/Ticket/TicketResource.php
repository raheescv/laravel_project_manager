<?php

namespace App\Http\Resources\Ticket;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ticket
 */
class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => (string) $this->description,
            'status' => $this->status,
            'group' => $this->group,
            'creator' => $this->creator?->name,
            'updater' => $this->updater?->name,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'attachments' => $this->attachments->map(fn ($attachment): array => [
                'id' => $attachment->id,
                'name' => $attachment->file_name,
                'url' => '/storage/'.ltrim($attachment->file_path, '/'),
                'mime' => $attachment->mime_type,
                'size' => (int) $attachment->file_size,
                'is_image' => $attachment->isImage(),
                'is_video' => $attachment->isVideo(),
                'created_at' => $attachment->created_at?->toIso8601String(),
            ])->values(),
            'comments' => $this->comments->map(fn ($comment): array => [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'author' => $comment->creator->name ?? 'User',
                'created_at' => $comment->created_at?->toIso8601String(),
                'edited' => $comment->updated_at && $comment->created_at && $comment->updated_at->gt($comment->created_at),
            ])->values(),
        ];
    }
}
