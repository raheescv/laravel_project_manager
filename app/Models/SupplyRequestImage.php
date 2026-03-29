<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SupplyRequestImage extends Model
{
    protected $fillable = [
        'supply_request_id',
        'name',
        'path',
        'type',
    ];

    public function getIsVideoAttribute(): bool
    {
        return Str::contains($this->type ?? '', ['video']);
    }

    public function getIsPdfAttribute(): bool
    {
        return Str::contains($this->type ?? '', ['pdf']);
    }

    public function supplyRequest(): BelongsTo
    {
        return $this->belongsTo(SupplyRequest::class);
    }

    public function storeFile($file, $supplyRequestId): array
    {
        try {
            $target = 'SupplyRequest/'.$supplyRequestId;
            $fileName = time().'_'.Str::random(8).'.'.$file->getClientOriginalExtension();
            $file->storeAs($target, $fileName, 'public');

            return [
                'success' => true,
                'fileName' => $fileName,
                'path' => '/storage/'.$target.'/'.$fileName,
                'type' => $file->getMimeType(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteFile(): void
    {
        $file = 'SupplyRequest/'.$this->supply_request_id.'/'.$this->name;
        $path = storage_path('app/public/'.$file);
        if (File::exists($path)) {
            File::delete($path);
        }
    }
}
