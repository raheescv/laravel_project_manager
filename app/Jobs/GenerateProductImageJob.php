<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateProductImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $referenceImagePath;

    public function __construct(
        protected string $category,
        protected string $serviceName
    ) {}

    public function handle()
    {
        try {

            $basePrompt = "A high-quality product photo of a '{$this->category}' item labeled '{$this->serviceName}'. The product is in a modern, elegant bottle or tube, placed on a clean, minimal background with soft natural lighting. The packaging should feel organic and fresh, nature-inspired";

            info($basePrompt);
            $workflow = $this->buildWorkflow($basePrompt);

            $response = Http::timeout(15)->post('http://127.0.0.1:8187/prompt', [
                'prompt' => $workflow,
            ]);

            if (! $response->successful()) {
                Log::error('ComfyUI API Error', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'workflow' => $workflow,
                ]);
                throw new \Exception('ComfyUI API error: '.$response->body());
            }

            $promptId = $response->json('prompt_id');
            if (! $promptId) {
                throw new \Exception('No prompt ID returned from ComfyUI');
            }

            $timeout = time() + 180; // 3 minute timeout
            $lastProgress = 0;
            $startTime = time();

            while (time() < $timeout) {
                $progressResponse = Http::get('http://127.0.0.1:8187/prompt/progress');
                $progress = $progressResponse->json();

                if (isset($progress['value']) && $progress['value'] != $lastProgress) {
                    $lastProgress = $progress['value'];
                    Log::info("Image generation progress: {$progress['value']}%");
                }

                $historyResponse = Http::get("http://127.0.0.1:8187/history/{$promptId}");
                if (! $historyResponse->successful()) {
                    continue;
                }

                $history = $historyResponse->json();
                if (isset($history[$promptId]['outputs']['save_image']['images'][0])) {
                    $imageName = $history[$promptId]['outputs']['save_image']['images'][0]['filename'];
                    $generationTime = time() - $startTime;

                    Log::info("Image generation completed in {$generationTime} seconds", [
                        'prompt_id' => $promptId,
                        'generation_time' => $generationTime,
                    ]);

                    return [
                        'success' => true,
                        'basePrompt' => $basePrompt,
                        'image_path' => "http://127.0.0.1:8187/view/{$imageName}",
                        'download_path' => 'http://127.0.0.1:8187/view?'.http_build_query([
                            'filename' => $imageName,
                            'type' => 'output',
                        ]),
                        'generation_time' => $generationTime,
                    ];
                }

                if (isset($history[$promptId]['error'])) {
                    throw new \Exception('ComfyUI execution error: '.$history[$promptId]['error']);
                }

                usleep(1000000); // 1 second between checks
            }

            throw new \Exception('Image generation timed out after 3 minutes');
        } catch (\Exception $e) {
            Log::error('Image generation failed', [
                'error' => $e->getMessage(),
                'category' => $this->category,
                'serviceName' => $this->serviceName,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to generate image: '.$e->getMessage(),
            ];
        }
    }

    private function buildWorkflow(string $basePrompt): array
    {
        $characterPrompt = 'full body shot, beautiful young woman, realistic, elegant pose, professional model, photorealistic, high-quality skin texture, soft smile, natural makeup, modern fashion, studio lighting, 8k uhd, high detail skin';
        $negativePrompt = 'blurry, low quality, bad anatomy, distorted, disfigured, poorly drawn face, poor facial details, poorly drawn hands, poorly rendered hands, poorly drawn feet, poorly rendered feet, missing fingers, extra limbs, fused fingers, too many fingers, long neck, cross-eye, cloned face, weird eyes, face defects';

        return [
            'empty_latent' => [
                'class_type' => 'EmptyLatentImage',
                'inputs' => [
                    'batch_size' => 1,
                    'height' => 512,
                    'width' => 512,
                ],
            ],
            'checkpoint' => [
                'class_type' => 'CheckpointLoaderSimple',
                'inputs' => [
                    'ckpt_name' => 'Realistic_Vision_V5.1-inpainting.safetensors',
                ],
            ],
            'positive_prompt' => [
                'class_type' => 'CLIPTextEncode',
                'inputs' => [
                    'clip' => ['checkpoint', 1],
                    'text' => $basePrompt.', '.$characterPrompt,
                ],
            ],
            'negative_prompt' => [
                'class_type' => 'CLIPTextEncode',
                'inputs' => [
                    'clip' => ['checkpoint', 1],
                    'text' => $negativePrompt,
                ],
            ],
            'sampler' => [
                'class_type' => 'KSampler',
                'inputs' => [
                    'cfg' => 7,
                    'denoise' => 1.0,
                    'latent_image' => ['empty_latent', 0],
                    'model' => ['checkpoint', 0],
                    'negative' => ['negative_prompt', 0],
                    'positive' => ['positive_prompt', 0],
                    'sampler_name' => 'euler_ancestral',
                    'scheduler' => 'normal',
                    'seed' => rand(1, 999999999),
                    'steps' => 30,
                ],
            ],
            'decoder' => [
                'class_type' => 'VAEDecode',
                'inputs' => [
                    'samples' => ['sampler', 0],
                    'vae' => ['checkpoint', 2],
                ],
            ],
            'save_image' => [
                'class_type' => 'SaveImage',
                'inputs' => [
                    'filename_prefix' => 'generated',
                    'images' => ['decoder', 0],
                ],
            ],
        ];
    }
}
