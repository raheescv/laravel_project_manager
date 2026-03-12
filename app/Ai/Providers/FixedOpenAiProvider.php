<?php

namespace App\Ai\Providers;

use Laravel\Ai\Providers\OpenAiProvider as BaseOpenAiProvider;

class FixedOpenAiProvider extends BaseOpenAiProvider
{
    /**
     * Get the default / normalized image options for the provider.
     *
     * Overridden to remove the 'moderation' parameter which causes 400 errors with OpenAI's DALL-E 3 API.
     */
    public function defaultImageOptions(?string $size = null, $quality = null): array
    {
        $options = parent::defaultImageOptions($size, $quality);

        // Remove the 'moderation' parameter which causes 400 errors with OpenAI's DALL-E 3 API.
        if (isset($options['moderation'])) {
            unset($options['moderation']);
        }

        // Map quality values correctly for OpenAI DALL-E 3
        $options['quality'] = match ($quality) {
            'high' => 'hd',
            'low', 'medium' => 'standard',
            default => 'standard',
        };

        // Force base64 response format because the SDK expects it
        $options['response_format'] = 'b64_json';

        return $options;
    }
}
