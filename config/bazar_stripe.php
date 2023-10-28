<?php

return [

    'api_key' => env('STRIPE_API_KEY'),

    'secret' => env('STRIPE_SECRET'),

    'success_url' => env('STRIPE_SUCCESS_URL', '/'),

    'cancel_url' => env('STRIPE_CANCEL_URL', '/'),

];
