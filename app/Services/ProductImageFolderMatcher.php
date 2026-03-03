<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class ProductImageFolderMatcher
{
    public function buildDropboxDownloadUrl(string $sharedUrl): string
    {
        $parts = parse_url(trim($sharedUrl));

        if (! isset($parts['host']) || ! str_contains($parts['host'], 'dropbox.com')) {
            throw new RuntimeException('Only Dropbox shared folder links are supported.');
        }

        parse_str($parts['query'] ?? '', $query);
        $query['dl'] = '1';

        $rebuiltQuery = http_build_query($query);
        $path = $parts['path'] ?? '';
        $scheme = $parts['scheme'] ?? 'https';

        return "{$scheme}://{$parts['host']}{$path}".($rebuiltQuery !== '' ? "?{$rebuiltQuery}" : '');
    }

    public function collectCodesFromDropboxFolder(string $sharedUrl): array
    {
        $downloadUrl = $this->buildDropboxDownloadUrl($sharedUrl);
        $temporaryZipPath = tempnam(sys_get_temp_dir(), 'dropbox-folder-');

        if ($temporaryZipPath === false) {
            throw new RuntimeException('Unable to create a temporary file for Dropbox download.');
        }

        $response = Http::timeout(300)
            ->withOptions(['sink' => $temporaryZipPath, 'allow_redirects' => true])
            ->get($downloadUrl);

        if (! $response->successful()) {
            @unlink($temporaryZipPath);

            throw new RuntimeException('Dropbox folder download failed.');
        }

        try {
            return $this->collectCodesFromZip($temporaryZipPath);
        } finally {
            @unlink($temporaryZipPath);
        }
    }

    public function importMatchedImagesFromDropboxFolder(string $sharedUrl): array
    {
        $downloadUrl = $this->buildDropboxDownloadUrl($sharedUrl);
        $temporaryZipPath = tempnam(sys_get_temp_dir(), 'dropbox-folder-');

        if ($temporaryZipPath === false) {
            throw new RuntimeException('Unable to create a temporary file for Dropbox download.');
        }

        $response = Http::timeout(300)
            ->withOptions(['sink' => $temporaryZipPath, 'allow_redirects' => true])
            ->get($downloadUrl);

        if (! $response->successful()) {
            @unlink($temporaryZipPath);

            throw new RuntimeException('Dropbox folder download failed.');
        }

        try {
            return $this->importMatchedImagesFromZip($temporaryZipPath);
        } finally {
            @unlink($temporaryZipPath);
        }
    }

    public function collectCodesFromZip(string $zipPath): array
    {
        $entries = $this->collectImageEntriesFromZip($zipPath);

        return array_values(array_unique(array_column($entries, 'normalized_code')));
    }

    public function importMatchedImagesFromZip(string $zipPath): array
    {
        $entries = collect($this->collectImageEntriesFromZip($zipPath));
        $productsByCode = Product::query()
            ->get(['id', 'code', 'name', 'thumbnail'])
            ->filter(fn (Product $product) => $this->normalizeCode((string) $product->code) !== '')
            ->keyBy(fn (Product $product) => $this->normalizeCode((string) $product->code));

        $zip = new ZipArchive();
        $openResult = $zip->open($zipPath);

        if ($openResult !== true) {
            throw new RuntimeException('Downloaded Dropbox folder is not a valid ZIP archive.');
        }

        $summary = [
            'total_file_codes' => $entries->pluck('normalized_code')->unique()->count(),
            'matched_product_codes' => 0,
            'imported_images' => 0,
            'skipped_duplicates' => 0,
            'missing_product_codes' => 0,
            'matched_products' => [],
            'missing_codes' => [],
        ];

        $matchedProducts = collect();
        $missingCodes = collect();

        foreach ($entries as $entry) {
            /** @var Product|null $product */
            $product = $productsByCode->get($entry['normalized_code']);

            if (! $product) {
                $missingCodes->push($entry['normalized_code']);

                continue;
            }

            $matchedProducts->put($product->id, [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
            ]);

            $existingImage = $product->images()
                ->where('method', 'normal')
                ->where('name', $entry['basename'])
                ->exists();

            if ($existingImage) {
                $summary['skipped_duplicates']++;

                continue;
            }

            $content = $zip->getFromIndex($entry['index']);

            if ($content === false) {
                continue;
            }

            $relativePath = 'products/'.$product->id.'/'.$this->generateStoredFilename($entry['basename'], $entry['extension']);
            Storage::disk('public')->put($relativePath, $content);
            $publicPath = url('storage/'.$relativePath);

            $product->images()->create([
                'name' => $entry['basename'],
                'size' => strlen($content),
                'type' => $entry['extension'],
                'method' => 'normal',
                'path' => $publicPath,
            ]);

            if (blank($product->thumbnail)) {
                $product->update(['thumbnail' => $publicPath]);
            }

            $summary['imported_images']++;
        }

        $zip->close();

        $summary['matched_product_codes'] = $matchedProducts->count();
        $summary['missing_product_codes'] = $missingCodes->unique()->count();
        $summary['matched_products'] = $matchedProducts->values()->all();
        $summary['missing_codes'] = $missingCodes->unique()->take(50)->values()->all();

        return $summary;
    }

    public function collectImageEntriesFromZip(string $zipPath): array
    {
        $zip = new ZipArchive();
        $openResult = $zip->open($zipPath);

        if ($openResult !== true) {
            throw new RuntimeException('Downloaded Dropbox folder is not a valid ZIP archive.');
        }

        $entries = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryName = $zip->getNameIndex($index);

            if (! is_string($entryName) || str_ends_with($entryName, '/')) {
                continue;
            }

            $extension = strtolower(pathinfo($entryName, PATHINFO_EXTENSION));

            if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg'], true)) {
                continue;
            }

            $filename = pathinfo($entryName, PATHINFO_FILENAME);
            $normalizedCode = $this->normalizeCode($filename);

            if ($normalizedCode !== '') {
                $entries[] = [
                    'index' => $index,
                    'entry_name' => $entryName,
                    'basename' => basename($entryName),
                    'extension' => $extension,
                    'normalized_code' => $normalizedCode,
                ];
            }
        }

        $zip->close();

        return $entries;
    }

    public function normalizeCode(string $value): string
    {
        return strtolower(trim($value));
    }

    protected function generateStoredFilename(string $originalName, string $extension): string
    {
        $filename = pathinfo($originalName, PATHINFO_FILENAME);
        $slug = Str::slug($filename);
        $slug = $slug !== '' ? $slug : 'image';

        return $slug.'-'.Str::random(8).'.'.$extension;
    }
}
