<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProductImageUrlImporter
{
    private const MAX_BYTES = 10 * 1024 * 1024;

    /** @var array<string, string> */
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/bmp' => 'bmp',
        'image/svg+xml' => 'svg',
    ];

    /**
     * Split a spreadsheet cell holding one or more image links (comma, pipe, semicolon or newline separated).
     *
     * @return array<int, string>
     */
    public function parseUrls(mixed $cell): array
    {
        if (! is_string($cell) || trim($cell) === '') {
            return [];
        }

        return collect(preg_split('/[\s,|;]+/', trim($cell)) ?: [])
            ->map(fn (string $url) => trim($url))
            ->filter(fn (string $url) => filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $url))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Rewrite share links (Dropbox, Google Drive) into direct-download links.
     */
    public function directDownloadUrl(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if (str_contains($host, 'dropbox.com')) {
            $url = preg_replace('/([?&])dl=0/', '$1raw=1', $url);

            return str_contains($url, 'raw=1') || str_contains($url, 'dl=1')
                ? $url
                : $url.(str_contains($url, '?') ? '&' : '?').'raw=1';
        }

        if (str_contains($host, 'drive.google.com')
            && preg_match('#/file/d/([^/]+)#', $url, $matches)) {
            return 'https://drive.google.com/uc?export=download&id='.$matches[1];
        }

        return $url;
    }

    /**
     * Download each link and attach it to the product as a normal image; the first one becomes the thumbnail when none is set.
     *
     * @param  array<int, string>  $urls
     * @return array{imported: int, skipped: int, failed: array<int, string>}
     */
    public function import(Product $product, array $urls): array
    {
        $summary = ['imported' => 0, 'skipped' => 0, 'failed' => []];

        foreach ($urls as $url) {
            $name = $this->nameFor($url);

            if ($product->images()->where('method', 'normal')->where('name', $name)->exists()) {
                $summary['skipped']++;

                continue;
            }

            try {
                $response = Http::timeout(60)->withOptions(['allow_redirects' => true])->get($this->directDownloadUrl($url));
                $content = $response->body();
                $mime = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
                $extension = self::EXTENSIONS[$mime] ?? null;

                if (! $response->successful() || $extension === null || $content === '' || strlen($content) > self::MAX_BYTES) {
                    throw new \RuntimeException('Not a downloadable image (HTTP '.$response->status().', '.($mime ?: 'unknown type').').');
                }

                $relativePath = 'products/'.$product->id.'/'.(Str::slug(pathinfo($name, PATHINFO_FILENAME)) ?: 'image').'-'.Str::random(8).'.'.$extension;
                Storage::disk('public')->put($relativePath, $content);
                $publicPath = url('storage/'.$relativePath);

                $product->images()->create([
                    'name' => $name,
                    'size' => strlen($content),
                    'type' => $extension,
                    'method' => 'normal',
                    'path' => $publicPath,
                ]);

                if (blank($product->thumbnail)) {
                    $product->update(['thumbnail' => $publicPath]);
                }

                $summary['imported']++;
            } catch (Throwable $exception) {
                $summary['failed'][] = $url;
                Log::warning('Product image import from URL failed', [
                    'product_id' => $product->id,
                    'url' => $url,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $summary;
    }

    private function nameFor(string $url): string
    {
        $basename = basename((string) parse_url($url, PHP_URL_PATH));

        return Str::limit($basename !== '' ? $basename : md5($url), 191, '');
    }
}
