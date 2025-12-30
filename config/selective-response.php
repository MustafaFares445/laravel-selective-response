<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Selective Response
    |--------------------------------------------------------------------------
    |
    | When enabled, BaseApiResource will automatically filter responses
    | based on model attributes loaded via select() queries.
    |
    */

    'enabled' => env('SELECTIVE_RESPONSE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Always Include Fields
    |--------------------------------------------------------------------------
    |
    | Fields that should always be included in responses, even if not
    | selected in the query. This is a global setting that applies to
    | all resources unless overridden.
    |
    */

    'always_include' => [
        // 'id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Scramble Extension Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Scramble API documentation extension.
    | This extension automatically detects select() calls and updates
    | the API documentation to show only selected fields.
    |
    */

    'scramble' => [
        'enabled' => env('SELECTIVE_RESPONSE_SCRAMBLE_ENABLED', true),

        'always_include_in_docs' => [
            // 'id',
        ],
    ],
];

