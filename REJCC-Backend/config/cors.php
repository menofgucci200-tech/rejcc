<?php

return [
    // Le front (Next.js) appelle /api/* — on autorise le CORS dessus.
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // En production, restreindre à l'origine du front (ex. https://www.rejcc.ci).
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
