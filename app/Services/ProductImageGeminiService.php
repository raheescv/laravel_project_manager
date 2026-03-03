<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Image;
use Laravel\Ai\Responses\ImageResponse;

class ProductImageGeminiService
{
    private const MAX_ATTEMPTS = 3;

    private const RETRY_DELAYS_SECONDS = [5, 15];

    private const COOL_DOWN_SECONDS = 90;

    private const COOL_DOWN_CACHE_KEY = 'ai:gemini:image:cooldown_until';

    public function generateAndAttach(Product $product, ?string $prompt = null, bool $setAsThumbnail = true): array
    {
        try {
            $remainingCoolDown = $this->remainingCoolDownSeconds();
            // if ($remainingCoolDown > 0) {
            //     return [
            //         'success' => false,
            //         'message' => "Gemini free API is rate limited. Please retry after {$remainingCoolDown} seconds.",
            //     ];
            // }

            $finalPrompt = trim($prompt ?: $this->buildPrompt($product));
            dd($finalPrompt);
            if ($finalPrompt === '') {
                return [
                    'success' => false,
                    'message' => 'Prompt is empty. Please provide a valid prompt.',
                ];
            }
            $response = $this->generateWithRetry($finalPrompt);
            $generatedImage = $response->firstImage();
            $extension = $this->extensionFromMime($generatedImage->mime);
            $filename = 'gemini-'.now()->format('YmdHis').'-'.Str::random(8).$extension;

            $storedPath = $response->storePubliclyAs(
                'products/'.$product->id,
                $filename,
                'public',
            );

            if (! $storedPath) {
                throw new \RuntimeException('Failed to store generated image.');
            }

            $publicPath = url('storage/'.$storedPath);
            $size = Storage::disk('public')->size($storedPath);

            $productImage = $product->images()->create([
                'name' => $filename,
                'size' => $size ?: 0,
                'type' => ltrim($extension, '.'),
                'method' => 'normal',
                'path' => $publicPath,
            ]);

            if ($setAsThumbnail || blank($product->thumbnail)) {
                $product->update(['thumbnail' => $publicPath]);
            }

            return [
                'success' => true,
                'message' => 'Product image downloaded successfully with Gemini.',
                'data' => $productImage,
                'path' => $publicPath,
                'prompt' => $finalPrompt,
            ];
        } catch (\Throwable $e) {
            report($e);

            $message = $e->getMessage();
            if ($e instanceof RateLimitedException || str_contains(strtolower($message), 'rate limit')) {
                $this->startCoolDown();
                $message = 'Gemini free API is rate limited. Please retry after '.$this->remainingCoolDownSeconds().' seconds.';
            }

            return [
                'success' => false,
                'message' => $message,
            ];
        }
    }

    private function generateWithRetry(string $prompt): ImageResponse
    {
        $attempt = 0;
        $lastError = null;
        $models = $this->imageModels();

        while ($attempt < self::MAX_ATTEMPTS) {
            foreach ($models as $model) {
                try {
                    return Image::of($prompt)
                        ->square()
                        ->quality('low')
                        ->generate('gemini', $model);
                } catch (RateLimitedException $e) {
                    $lastError = $e;

                    continue;
                } catch (\Throwable $e) {
                    $lastError = $e;

                    continue;
                }
            }

            $attempt++;

            if ($attempt < self::MAX_ATTEMPTS) {
                $delay = self::RETRY_DELAYS_SECONDS[$attempt - 1] ?? 15;
                sleep($delay);
            }
        }

        if ($lastError instanceof RateLimitedException) {
            $this->startCoolDown();
            throw $lastError;
        }

        throw $lastError ?: new \RuntimeException('Image generation failed after retries.');
    }

    private function imageModels(): array
    {
        $configured = config('services.gemini.image_models', []);

        if (is_string($configured)) {
            $configured = array_map('trim', explode(',', $configured));
        }

        $models = array_values(array_filter($configured, fn ($value) => is_string($value) && $value !== ''));

        if (count($models) > 0) {
            return $models;
        }

        return [
            'gemini-2.5-flash-image-preview',
            'gemini-3-pro-image-preview',
        ];
    }

    private function startCoolDown(): void
    {
        $until = now()->addSeconds(self::COOL_DOWN_SECONDS)->timestamp;
        Cache::put(self::COOL_DOWN_CACHE_KEY, $until, now()->addSeconds(self::COOL_DOWN_SECONDS));
    }

    private function remainingCoolDownSeconds(): int
    {
        $until = (int) Cache::get(self::COOL_DOWN_CACHE_KEY, 0);
        $remaining = $until - now()->timestamp;

        return $remaining > 0 ? $remaining : 0;
    }

    private function buildPrompt(Product $product): string
    {
        $product->loadMissing('mainCategory', 'department');

        $name = trim((string) $product->name);
        $category = trim((string) optional($product->mainCategory)->name);
        $department = trim((string) optional($product->department)->name);
        $brand = trim((string) optional($product->brand)->name);
        $description = trim((string) ($product->description ?? ''));
        $parts = array_filter([
            "Ultra-realistic studio product photography of {$name}.",
            // $category !== '' ? "Category reference: {$category}." : null,
            // $department !== '' ? "Department reference: {$department}." : null,
            $description !== '' ? "Product details: {$description}." : null,
            $brand !== '' ? "Brand reference: {$brand}." : null,
            'Side profile, perfectly centered.',
            '85mm lens, f/11, ISO 100.',
            'Softbox lighting left and right.',
            'Clean pure white seamless background.',
            'Natural soft shadow below shoe.',
            'High-detail texture, sharp stitching.',
            'No logo, no watermark, no text.',
            'E-commerce catalog style.',
        ]);

        return implode(' ', $parts);
    }

    private function extensionFromMime(?string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => '.jpg',
            'image/webp' => '.webp',
            default => '.png',
        };
    }
}
