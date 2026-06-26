<?php

return [

    'name' => env('API_NAME', env('APP_NAME', 'Larapi')),

    'version' => env('API_VERSION', 'v1'),

    'prefix' => env('API_PREFIX', 'api'),

    'rate_limit' => env('API_RATE_LIMIT', '60,1'),

    'documentation' => [
        'enabled' => (bool) env('API_DOCS_ENABLED', true),
        'path' => env('API_DOCS_PATH', 'docs'),
        'json_path' => env('API_DOCS_JSON_PATH', 'docs/openapi.json'),
        'yaml_path' => env('API_DOCS_YAML_PATH', 'docs/openapi.yaml'),
        'output_path' => env('API_DOCS_OUTPUT_PATH', storage_path('api-docs/openapi.json')),
        'description' => env('API_DOCS_DESCRIPTION', 'Interactive Swagger documentation for the Larapi Core API.'),
        'contact_email' => env('API_DOCS_CONTACT_EMAIL'),
        'swagger_ui_assets_url' => env('API_DOCS_SWAGGER_UI_ASSETS_URL', 'https://cdn.jsdelivr.net/npm/swagger-ui-dist@5'),
        'extensions' => [],
    ],

];
