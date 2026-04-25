<?php

return [
    /*
     | Default translation provider: 'deepl' or 'google'
     */
    'provider' => env('TRANSLATE_PROVIDER', 'deepl'),

    /*
     | API key for the chosen provider.
     | DeepL: get from deepl.com/account/summary
     | Google: Cloud Translation API key from console.cloud.google.com
     */
    'api_key' => env('TRANSLATE_API_KEY', ''),

    /*
     | DeepL Pro API? When true, uses api.deepl.com instead of api-free.deepl.com.
     */
    'deepl_pro' => env('TRANSLATE_DEEPL_PRO', false),
];
