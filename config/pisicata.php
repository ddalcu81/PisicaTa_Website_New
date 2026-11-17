<?php

return [
    'indexable' => env('SITE_INDEXABLE', false),
    'adsense' => [
        'enabled' => env('ADSENSE_ENABLED', false),
        'client' => env('ADSENSE_CLIENT'),
        'rail' => env('ADSENSE_RAIL_SLOT'),
        'article' => env('ADSENSE_ARTICLE_SLOT'),
    ],
];
