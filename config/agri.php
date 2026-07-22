<?php

return [
    // Enabled content locales for translatable data (SPEC §3.5).
    'locales' => array_filter(array_map('trim', explode(',', (string) env('CONTENT_LOCALES', 'fr')))),

    // Fallback/default content locale.
    'default_locale' => env('APP_LOCALE', 'fr'),
];
