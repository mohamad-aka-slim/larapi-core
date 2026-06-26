<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\OpenApiSpecification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DocumentationController extends Controller
{
    public function ui(): Response
    {
        $title = e(config('api.name', config('app.name', 'Larapi')).' API Documentation');
        $jsonUrl = e('/'.trim((string) config('api.prefix', 'api'), '/').'/'.trim((string) config('api.documentation.json_path', 'docs/openapi.json'), '/'));
        $assetsUrl = e(rtrim((string) config('api.documentation.swagger_ui_assets_url'), '/'));

        $html = <<<HTML
            <!doctype html>
            <html lang="en">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>{$title}</title>
                <link rel="stylesheet" href="{$assetsUrl}/swagger-ui.css">
                <style>
                    body { margin: 0; background: #f7f7f7; }
                    .swagger-ui .topbar { display: none; }
                </style>
            </head>
            <body>
                <div id="swagger-ui"></div>
                <script src="{$assetsUrl}/swagger-ui-bundle.js"></script>
                <script src="{$assetsUrl}/swagger-ui-standalone-preset.js"></script>
                <script>
                    window.ui = SwaggerUIBundle({
                        url: '{$jsonUrl}',
                        dom_id: '#swagger-ui',
                        deepLinking: true,
                        presets: [
                            SwaggerUIBundle.presets.apis,
                            SwaggerUIStandalonePreset
                        ],
                        layout: 'StandaloneLayout',
                        persistAuthorization: true
                    });
                </script>
            </body>
            </html>
        HTML;

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function json(OpenApiSpecification $openApi): JsonResponse
    {
        return response()->json($openApi->toArray(), 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function yaml(OpenApiSpecification $openApi): Response
    {
        return response($openApi->toYaml())->header('Content-Type', 'application/yaml; charset=UTF-8');
    }
}
