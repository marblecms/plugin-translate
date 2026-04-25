<?php

namespace MarbleCms\Translate\Services\Providers;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * DeepL Translation API v2 provider.
 *
 * Uses the free API endpoint (api-free.deepl.com) by default.
 * Set TRANSLATE_DEEPL_PRO=true to use the Pro endpoint (api.deepl.com).
 */
class DeepLProvider
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey  = config('translate.api_key', '');
        $this->baseUrl = config('translate.deepl_pro', false)
            ? 'https://api.deepl.com/v2'
            : 'https://api-free.deepl.com/v2';
    }

    /**
     * Translate an array of texts.
     *
     * @param  string[] $texts
     * @return string[]  Translated texts in the same order
     * @throws RuntimeException
     */
    public function translate(array $texts, string $sourceLang, string $targetLang): array
    {
        if (empty($texts)) {
            return [];
        }

        if (empty($this->apiKey)) {
            throw new RuntimeException('DeepL API key is not configured. Set TRANSLATE_API_KEY in your .env.');
        }

        // DeepL expects ISO 639-1 codes (uppercase for target, source is optional)
        $sourceCode = strtoupper(substr($sourceLang, 0, 2));
        $targetCode = strtoupper(substr($targetLang, 0, 2));

        // Build query parameters — DeepL accepts repeated `text` params
        $params = ['target_lang' => $targetCode, 'source_lang' => $sourceCode];
        foreach ($texts as $text) {
            $params['text'][] = $text;
        }

        $response = Http::withHeaders([
            'Authorization' => 'DeepL-Auth-Key ' . $this->apiKey,
        ])->asForm()->post("{$this->baseUrl}/translate", $params);

        if (!$response->successful()) {
            throw new RuntimeException(
                "DeepL API error {$response->status()}: " . $response->body()
            );
        }

        $data = $response->json();

        if (!isset($data['translations']) || !is_array($data['translations'])) {
            throw new RuntimeException('Unexpected DeepL API response format.');
        }

        return array_column($data['translations'], 'text');
    }
}
