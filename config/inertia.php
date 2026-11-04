<?php

// Only the settings Banyira changes. Everything else uses the package defaults.
return [

    'pages' => [
        'ensure_pages_exist' => false,
        'paths' => [resource_path('js/Pages')],
        'extensions' => ['vue', 'ts'],
    ],

    'testing' => [
        'ensure_pages_exist' => true,
    ],

];
