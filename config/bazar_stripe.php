<?php

return [

    'test_mode' => env('STRIPE_TEST_MODE', false),

    'api_key' => env('STRIPE_API_KEY'),

    'secret' => env('STRIPE_SECRET'),

    'success_url' => env('STRIPE_SUCCESS_URL', '/'),

    'failure_url' => env('STRIPE_FAILURE_URL', '/'),

];
