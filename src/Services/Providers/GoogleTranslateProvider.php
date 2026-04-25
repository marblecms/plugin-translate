<?php

namespace MarbleCms\Translate\Services\Providers;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Google Cloud Translation API v2 (Basic) provider.
 *
 * Requires a Cloud Translation API key set in TRANSLATE_API_KEY.
 * Enable the API at: https://console.cloud.google.com/apis/library/translate.googleapis.com
 */
class GoogleTranslateProvider
{
    protected const ENDPOINT = 'https://translation.googleapis.com/language/translate/v2';

    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('translate.api_key', '');
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
            throw new RuntimeException('Google Translate API key is not configured. Set TRANSLATE_API_KEY in your .env.');
        }

        // Google uses ISO 639-1 lowercase 2-letter codes
        $sourceCode = strtolower(substr($sourceLang, 0, 2));
        $targetCode = strtolower(substr($targetLang, 0, 2));

        $response = Http::post(self::ENDPOINT, [
            'key'    => $this->apiKey,
            'q'      => $texts,
            'source' => $sourceCode,
            'target' => $targetCode,
            'format' => 'html',
        ]);

        if (!$response->successful()) {
            throw new RuntimeException(
                "Google Translate API error {$response->status()}: " . $response->body()
            );
        }

        $data = $response->json();

        if (!isset($data['data']['translations']) || !is_array($data['data']['translations'])) {
            throw new RuntimeException('Unexpected Google Translate API response format.');
        }

        return array_column($data['data']['translations'], 'translatedText');
    }
}
