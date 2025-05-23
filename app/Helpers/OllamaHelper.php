<?php

namespace App\Helpers;

use Exception;
use Ollama\OllamaClient;

class OllamaHelper
{
    private string $model = 'openchat';

    private $client;

    public function __construct()
    {
        $this->client = new OllamaClient();
        if (! $this->client->isRunning()) {
            throw new Exception('Ollama API is not running', 1);
        }

    }

    public function generatePromptForServiceImage(string $category, string $service_name): array
    {
        try {
            if (! $this->client->isRunning()) {
                throw new Exception('Ollama API is not running', 1);
            }

            $systemPrompt = <<<PROMPT
            Generate a focused, professional image prompt for a salon service. The prompt should:
            - Describe the specific results of a {$category} service named "{$service_name}"
            - Focus on hair style, texture, and appearance details
            - Describe the professional salon setting and lighting
            - Do NOT include model characteristics (these will be added separately)
            - Return only the prompt text, no explanations
            - High Quality, realistic, and detailed
            Example format: "Professional {service_name} result, [hair details], [styling details], [environment details]"
            PROMPT;

            $response = $this->client->post('generate', [
                'model' => $this->model,
                'prompt' => $systemPrompt,
                'temperature' => 0.7,
            ]);

            if (! isset($response['response'])) {
                throw new Exception("Invalid response from {$this->model} API.");
            }

            $return['success'] = true;
            $return['data'] = [
                'prompt' => trim($response['response']),
                'category' => $category,
                'service_name' => $service_name,
            ];
            $return['message'] = 'Successfully Generated Image Prompt';
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = 'Error generating image prompt: '.$e->getMessage();
        }

        return $return;
    }

    public function analyzeText(string $text): ?array
    {
        try {
            $prompt = <<<PROMPT
                Analyze the following customer feedback and perform these tasks:
                    - Sentiment Analysis:
                        - Assign a main_sentiment_score between -1 and 1 (-1 = negative, 0 = neutral, 1 = positive) representing the overall sentiment of the feedback.
                    - Topic Detection:
                        - Identify the single main_detected_topic that best summarizes the overall subject of the feedback.
                        - Identify 3 to 5 sub_detected_topic entries (as an array of objects) representing specific aspects or subtopics mentioned in the feedback. Each object should contain:
                            - sub_topic: a string describing the subtopic.
                            - sentiment_score: a number between -1 and 1 indicating the sentiment for that subtopic
                    Response Format:
                        - Respond only with a valid JSON object. No extra text or explanation.
                        - Use only lowercase alphabets (a-z) for all keys and values. No other characters are allowed.
                        - The JSON structure must follow this format:
                        {
                            "main_sentiment_score": number,
                            "main_detected_topic": string,
                            "sub_detected_topic": [
                                {
                                    "sub_topic": string,
                                    "sentiment_score": number
                                },
                                ...
                            ]
                        }

                    Text: "$text"
                PROMPT;

            $response = $this->client->post('generate', [
                'model' => $this->model,
                'prompt' => $prompt,
                'temperature' => 0.0,
            ]);

            if (! isset($response['response'])) {
                throw new Exception("Invalid response from {$this->model} API.");
            }

            $jsonData = json_decode($response['response'], true);
            if (! isset($jsonData['main_sentiment_score'], $jsonData['main_detected_topic'], $jsonData['sub_detected_topic'])) {
                throw new Exception('Unexpected response format.');
            }

            $return['success'] = true;
            $return['data'] = $jsonData;
            $return['message'] = 'Successfully Generated';
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = 'Error analyzing text: '.$e->getMessage();
        }

        return $return;
    }

    public function paraphrasing($sentence, $style): ?array
    {
        try {
            if (! in_array($style, ['rephrase', 'grammar correction', 'expand', 'concise', 'formal', 'informal', 'quirky'])) {
                throw new Exception('Invalid style provided.');
            }
            $prompt = <<<PROMPT
                Alter the following sentence according to the specified style. Apply the style strictly and return only the modified sentence.
                Style: "$style"
                Sentence: "$sentence"
                PROMPT;

            $response = $this->client->post('generate', [
                'model' => $this->model,
                'prompt' => $prompt,
                'temperature' => 0.7,
            ]);

            if (! isset($response['response'])) {
                throw new Exception("Invalid response from {$this->model} API.");
            }

            $data['request'] = $sentence;
            $data['style'] = $style;
            $data['response'] = $response['response'];

            $return['success'] = true;
            $return['data'] = $data;
            $return['message'] = 'Successfully Generated';
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = 'Error analyzing text: '.$e->getMessage();
        }

        return $return;
    }

    public function generateReport($data, $prompt)
    {
        try {
            $prompt = 'Analyze this sales data and '.$prompt.". Return your analysis as a valid JSON array of objects, where each object represents a row and each key is a column name. Only return the JSON array, no explanation. Data:\n".json_encode($data);
            $response = $this->client->post('generate', [
                'model' => $this->model,
                'prompt' => $prompt,
                'stream' => false,
                'context' => [],
                'system' => 'You are a data analyst specialized in creating reports based on sales data. Your task is to analyze the data and provide insights.',
                'options' => [
                    'temperature' => 0.7,
                    'top_k' => 40,
                    'top_p' => 0.9,
                ],
            ]);
            if (! isset($response['response'])) {
                throw new Exception("Invalid response from {$this->model} API.");
            }
            $data = json_decode($response['response'], true);

            $return['success'] = true;
            $return['data'] = $data;
            $return['message'] = 'Successfully Generated';
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = 'Failed to generate report from Ollama: '.$e->getMessage();
        }

        return $return;
    }
}
