<?php

return [
    'max_batch_size' => env('MIXPANEL_MAX_BATCH_SIZE', 1),
    'max_queue_size' => env('MIXPANEL_MAX_QUEUE_SIZE', 1),
    'consumer' => env('MIXPANEL_CONSUMER', 'curl'),
    'host' => env('MIXPANEL_HOST', "api.mixpanel.com"),
    'events_endpoint' => env('MIXPANEL_EVENTS_ENDPOINT', "/track"),
    'people_endpoint' => env('MIXPANEL_PEOPLE_ENDPOINT', "/engage"),
    'use_ssl' => env('MIXPANEL_USE_SSL', true),
    "error_callback" => null,
    'timezone' => env('DEFAULT_DISPLAY_TIMEZONE', 'Asia/Ho_Chi_Minh'),
    "ignore_http_errors" => env('MIXPANEL_IGNORE_HTTP_ERROR', false)
];
