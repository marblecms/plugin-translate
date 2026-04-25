# Marble Translate

Machine translation for [Marble CMS](https://github.com/marblecms/admin) item field values using DeepL or Google Translate.

## Installation

```bash
composer require marblecms/marble-translate
php artisan marble:translate:install
```

Publish config:

```bash
php artisan vendor:publish --tag=translate-config
```

## Configuration (`config/translate.php`)

| Key | Default | Description |
|-----|---------|-------------|
| `provider` | `deepl` | Default provider: `deepl` or `google` |
| `api_key` | `''` | API key for the chosen provider |
| `deepl_pro` | `false` | Use DeepL Pro endpoint (api.deepl.com vs api-free.deepl.com) |

**.env variables:**

```
TRANSLATE_PROVIDER=deepl
TRANSLATE_API_KEY=your-key-here
TRANSLATE_DEEPL_PRO=false
```

## How it works

1. Admin visits **Structure → Translate** to see all published items with translatable fields
2. Items show a per-language coverage indicator (tick = translated, "Missing" = not yet)
3. Click the arrow button to open the translation workspace for an item
4. Select source language, target language, and provider; click **Translate**
5. The plugin calls the API and creates a **pending TranslationJob** with the results
6. Review the source vs. translated values in the diff table
7. Click **Apply All** to write the translations back to `item_values`, or **Reject** to discard

Applying a job fires the `ItemTranslated` event.

## Providers

### DeepL

Uses the [DeepL API v2](https://www.deepl.com/docs-api). Free tier uses `api-free.deepl.com`; Pro tier uses `api.deepl.com` (set `TRANSLATE_DEEPL_PRO=true`).

### Google Cloud Translation

Uses the [Cloud Translation Basic (v2) API](https://cloud.google.com/translate/docs/reference/rest). Requires a Cloud Translation API key.

## Admin nav

Accessible via **Structure → Translate**.

## Admin routes

| Method | URL | Route name | Description |
|--------|-----|-----------|-------------|
| GET | `/{prefix}/translate` | `marble.translate.index` | Items needing translation |
| GET | `/{prefix}/translate/item/{item}` | `marble.translate.show` | Translation workspace |
| POST | `/{prefix}/translate/item/{item}` | `marble.translate.translate` | Trigger API translation |
| POST | `/{prefix}/translate/job/{job}/apply` | `marble.translate.apply` | Apply pending job |
| POST | `/{prefix}/translate/job/{job}/reject` | `marble.translate.reject` | Reject pending job |

## Events

| Event | Properties |
|-------|-----------|
| `ItemTranslated` | `$item`, `$job` |

## Artisan Commands

| Command | Description |
|---------|-------------|
| `marble:translate:install` | Run migrations |

## Database

**`translation_jobs`**

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint | PK |
| `item_id` | bigint | FK → items (cascade delete) |
| `source_language_id` | bigint | FK → languages |
| `target_language_id` | bigint | FK → languages |
| `provider` | string | `deepl` or `google` |
| `status` | string | `pending`, `applied`, `rejected` |
| `translated_fields` | json | `{field_identifier: translated_value}` |
| `created_at` / `updated_at` | timestamps | |
| `applied_at` | timestamp | nullable — when job was applied |

## Extending with a custom provider

Implement a provider class with this interface:

```php
public function translate(array $texts, string $sourceLang, string $targetLang): array;
```

Then extend `TranslationService::resolveProvider()` or bind your provider in a service provider.
