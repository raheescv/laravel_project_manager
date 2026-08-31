<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
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

    public function summarizeMatchesFromZip(string $zipPath): array
    {
        $entries = collect($this->collectImageEntriesFromZip($zipPath));
        [$productIndex, $sortedMatchKeys] = $this->loadProductsForMatching();

        $matchedProducts = collect();
        $missingCodes = collect();
        $detectedCodes = collect();
        $matchedImageFiles = 0;

        foreach ($entries as $entry) {
            $match = $this->resolveMatch($entry['normalized_code'], $sortedMatchKeys, $productIndex);

            if ($match === null) {
                $missingCodes->push($entry['normalized_code']);
                $detectedCodes->push($entry['normalized_code']);

                continue;
            }

            foreach ($match['products'] as $product) {
                $this->rememberMatchedProduct($matchedProducts, $product, $match['matched_by']);
            }

            $detectedCodes->push($match['key']);
            $matchedImageFiles++;
        }

        return [
            'total_image_files' => $entries->count(),
            'matched_image_files' => $matchedImageFiles,
            'missing_image_files' => $entries->count() - $matchedImageFiles,
            'total_file_codes' => $detectedCodes->unique()->count(),
            'matching_product_codes' => $matchedProducts->count(),
            'missing_product_codes' => $missingCodes->unique()->count(),
            'matched_products' => $matchedProducts->values()->all(),
            'missing_codes' => $missingCodes->unique()->take(50)->values()->all(),
        ];
    }

    public function importMatchedImagesFromZip(string $zipPath): array
    {
        $entries = collect($this->collectImageEntriesFromZip($zipPath));
        [$productIndex, $sortedMatchKeys] = $this->loadProductsForMatching();

        $zip = new ZipArchive();
        $openResult = $zip->open($zipPath);

        if ($openResult !== true) {
            throw new RuntimeException('Downloaded Dropbox folder is not a valid ZIP archive.');
        }

        $summary = [
            'total_image_files' => $entries->count(),
            'matched_image_files' => 0,
            'missing_image_files' => 0,
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
        $detectedCodes = collect();

        foreach ($entries as $entry) {
            $match = $this->resolveMatch($entry['normalized_code'], $sortedMatchKeys, $productIndex);

            if ($match === null) {
                $missingCodes->push($entry['normalized_code']);
                $detectedCodes->push($entry['normalized_code']);

                continue;
            }

            $products = $match['products'];

            foreach ($products as $product) {
                $this->rememberMatchedProduct($matchedProducts, $product, $match['matched_by']);
            }
            $detectedCodes->push($match['key']);

            $content = $zip->getFromIndex($entry['index']);

            if ($content === false) {
                continue;
            }

            $anyImported = false;

            foreach ($products as $product) {
                $existingImage = $product->images()
                    ->where('method', 'normal')
                    ->where('name', $entry['basename'])
                    ->exists();

                if ($existingImage) {
                    $summary['skipped_duplicates']++;

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
                $anyImported = true;
            }

            if ($anyImported) {
                $summary['matched_image_files']++;
            }
        }

        $zip->close();

        $summary['missing_image_files'] = $summary['total_image_files'] - $summary['matched_image_files'];
        $summary['total_file_codes'] = $detectedCodes->unique()->count();
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
        return trim((string) preg_replace('/\s+/', ' ', strtolower(trim($value))));
    }

    /**
     * Separator-insensitive form so "blue-shirt.jpg" can still reach the product named "Blue Shirt".
     */
    public function normalizeLoose(string $value): string
    {
        return $this->normalizeCode(str_replace(['-', '_', '.'], ' ', $value));
    }

    /**
     * Build the lookup filenames are matched against: every product contributes its code
     * and its name (each in plain and separator-insensitive form).
     *
     * @return array{0: Collection, 1: Collection} [key => ['code' => products, 'name' => products], keys sorted longest-first]
     */
    protected function loadProductsForMatching(): array
    {
        $index = [];

        Product::query()
            ->select(['id', 'code', 'name', 'thumbnail'])
            ->get()
            ->each(function (Product $product) use (&$index): void {
                foreach ($this->matchKeysFor($product) as $source => $keys) {
                    foreach ($keys as $key) {
                        $index[$key][$source] ??= collect();
                        $index[$key][$source]->put($product->id, $product);
                    }
                }
            });

        $sortedMatchKeys = collect(array_map('strval', array_keys($index)))
            ->sortByDesc(fn (string $key) => strlen($key))
            ->values();

        return [collect($index), $sortedMatchKeys];
    }

    /**
     * @return array{code: array<int, string>, name: array<int, string>}
     */
    protected function matchKeysFor(Product $product): array
    {
        $keys = [];

        foreach (['code', 'name'] as $source) {
            $value = (string) ($product->{$source} ?? '');

            $keys[$source] = array_values(array_filter(array_unique([
                $this->normalizeCode($value),
                $this->normalizeLoose($value),
            ]), fn (string $key) => $key !== ''));
        }

        return $keys;
    }

    /**
     * A product can be reached by several filenames through different sources, so the
     * reported "matched by" accumulates instead of being overwritten by the last file.
     */
    protected function rememberMatchedProduct(Collection $matchedProducts, Product $product, string $matchedBy): void
    {
        $sources = collect(explode(',', (string) ($matchedProducts->get($product->id)['matched_by'] ?? '')))
            ->map(fn (string $source) => trim($source))
            ->filter()
            ->push($matchedBy)
            ->unique()
            ->sort()
            ->values();

        $matchedProducts->put($product->id, [
            'id' => $product->id,
            'code' => $product->code,
            'name' => $product->name,
            'matched_by' => $sources->implode(', '),
        ]);
    }

    /**
     * Resolve one image filename to its products, preferring a code hit over a name hit.
     *
     * @return array{key: string, products: Collection, matched_by: string}|null
     */
    protected function resolveMatch(string $filenameCode, Collection $sortedMatchKeys, Collection $index): ?array
    {
        $candidates = array_values(array_filter(array_unique([
            $filenameCode,
            $this->normalizeLoose($filenameCode),
        ]), fn (string $candidate) => $candidate !== ''));

        foreach ($candidates as $candidate) {
            $matchedKey = $index->has($candidate)
                ? $candidate
                : $this->resolveMatchingCode($candidate, $sortedMatchKeys);

            if ($matchedKey === null) {
                continue;
            }

            $bucket = $index->get($matchedKey, []);

            foreach (['code', 'name'] as $source) {
                /** @var Collection|null $products */
                $products = $bucket[$source] ?? null;

                if ($products && $products->isNotEmpty()) {
                    return ['key' => $matchedKey, 'products' => $products, 'matched_by' => $source];
                }
            }
        }

        return null;
    }

    protected function resolveMatchingCode(string $filenameCode, Collection $sortedMatchKeys): ?string
    {
        foreach ($sortedMatchKeys as $productCode) {
            if ($filenameCode === $productCode) {
                return $productCode;
            }

            if (! str_starts_with($filenameCode, $productCode)) {
                continue;
            }

            $suffix = substr($filenameCode, strlen($productCode));

            if ($suffix === '') {
                return $productCode;
            }

            if (preg_match('/^[-_\s(]/', $suffix) === 1) {
                return $productCode;
            }
        }

        return null;
    }

    protected function generateStoredFilename(string $originalName, string $extension): string
    {
        $filename = pathinfo($originalName, PATHINFO_FILENAME);
        $slug = Str::slug($filename);
        $slug = $slug !== '' ? $slug : 'image';

        return $slug.'-'.Str::random(8).'.'.$extension;
    }
}
