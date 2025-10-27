<?php

return [
    'client_id' => env('PLAID_CLIENT_ID'),
    'secret'    => env('PLAID_SECRET'),
    'env'       => env('PLAID_ENV', 'sandbox'),
    'countries' => array_values(array_filter(array_map('trim', explode(',', env('PLAID_COUNTRY', 'GB'))))),
    'products'  => array_values(array_filter(array_map('trim', explode(',', env('PLAID_PRODUCTS', 'transactions'))))),
    'redirect'  => env('PLAID_REDIRECT_URI', null),

    'base_urls' => [
        'sandbox'     => 'https://sandbox.plaid.com',
        'development' => 'https://development.plaid.com',
        'production'  => 'https://production.plaid.com',
    ],
];
