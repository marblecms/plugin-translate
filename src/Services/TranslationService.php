<?php

namespace MarbleCms\Translate\Services;

use Marble\Admin\Models\Item;
use Marble\Admin\Models\Language;
use Marble\Admin\Models\ItemValue;
use MarbleCms\Translate\Services\Providers\DeepLProvider;
use MarbleCms\Translate\Services\Providers\GoogleTranslateProvider;

class TranslationService
{
    /**
     * Collect translatable field values from the source language and
     * send them to the chosen provider.
     *
     * @return array<string, string>  Map of field_identifier => translated_value
     * @throws \RuntimeException on API error or missing config
     */
    public function translate(Item $item, int $sourceLangId, int $targetLangId, ?string $provider = null): array
    {
        $provider   = $provider ?? config('translate.provider', 'deepl');
        $sourceLang = Language::findOrFail($sourceLangId);
        $targetLang = Language::findOrFail($targetLangId);

        // Load all translatable field values for the source language
        $values = ItemValue::with('blueprintField')
            ->where('item_id', $item->id)
            ->where('language_id', $sourceLangId)
            ->whereHas('blueprintField', fn($q) => $q->where('translatable', true))
            ->get();

        if ($values->isEmpty()) {
            return [];
        }

        // Build texts array keyed by field identifier
        $texts = [];
        foreach ($values as $value) {
            $identifier = $value->blueprintField->identifier;
            $raw        = $value->value;

            // Only translate scalar text values (skip JSON/null)
            if (is_string($raw) && $raw !== '') {
                $texts[$identifier] = $raw;
            }
        }

        if (empty($texts)) {
            return [];
        }

        $providerInstance = $this->resolveProvider($provider);

        $sourceLangCode = $sourceLang->locale ?? $sourceLang->code;
        $targetLangCode = $targetLang->locale ?? $targetLang->code;

        // Translate array of texts in one API call
        $translated = $providerInstance->translate(
            array_values($texts),
            $sourceLangCode,
            $targetLangCode,
        );

        // Map back to field identifiers
        $result = [];
        $keys   = array_keys($texts);
        foreach ($keys as $i => $identifier) {
            $result[$identifier] = $translated[$i] ?? '';
        }

        return $result;
    }

    protected function resolveProvider(string $provider): DeepLProvider|GoogleTranslateProvider
    {
        return match ($provider) {
            'google' => new GoogleTranslateProvider(),
            default  => new DeepLProvider(),
        };
    }
}
