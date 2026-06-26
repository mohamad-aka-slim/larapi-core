<?php

namespace App\Support;

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class OpenApiSpecification
{
    private const RESOURCE_ACTIONS = [
        'index',
        'store',
        'show',
        'update',
        'destroy',
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $prefix = trim((string) config('api.prefix', 'api'), '/');
        $version = trim((string) config('api.version', 'v1'), '/');
        $basePath = $prefix === '' ? '' : '/'.$prefix;
        $serverUrl = rtrim((string) (config('api.documentation.server_url') ?: $basePath), '/') ?: '/';
        $resourceRoutes = $this->resourceRouteDocumentation($prefix);

        $specification = [
            'openapi' => '3.1.0',
            'info' => array_filter([
                'title' => (string) config('api.name', config('app.name', 'Larapi')),
                'version' => $version,
                'description' => (string) config('api.documentation.description'),
                'contact' => config('api.documentation.contact_email')
                    ? ['email' => (string) config('api.documentation.contact_email')]
                    : null,
            ]),
            'servers' => [
                [
                    'url' => $serverUrl,
                    'description' => 'Current API server',
                ],
            ],
            'tags' => [
                [
                    'name' => 'Status',
                    'description' => 'Readiness and API metadata.',
                ],
                [
                    'name' => 'Authentication',
                    'description' => 'Sanctum bearer token authentication.',
                ],
                ...$resourceRoutes['tags'],
            ],
            'paths' => array_replace_recursive($this->paths($version), $resourceRoutes['paths']),
            'components' => $this->components(),
        ];

        return array_replace_recursive($specification, config('api.documentation.extensions', []));
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options | JSON_THROW_ON_ERROR);
    }

    public function toYaml(): string
    {
        return rtrim($this->yaml($this->toArray()))."\n";
    }

    /**
     * @return array<string, mixed>
     */
    private function paths(string $version): array
    {
        return [
            "/{$version}/status" => [
                'get' => [
                    'tags' => ['Status'],
                    'summary' => 'Get API status',
                    'operationId' => 'getApiStatus',
                    'responses' => [
                        '200' => [
                            'description' => 'API metadata and readiness.',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/ApiMetadataResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            "/{$version}/auth/register" => [
                'post' => [
                    'tags' => ['Authentication'],
                    'summary' => 'Register a user',
                    'operationId' => 'registerUser',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/RegisterRequest',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Registration successful.',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/AuthResponse',
                                    ],
                                ],
                            ],
                        ],
                        '422' => [
                            '$ref' => '#/components/responses/ValidationError',
                        ],
                    ],
                ],
            ],
            "/{$version}/auth/login" => [
                'post' => [
                    'tags' => ['Authentication'],
                    'summary' => 'Log in a user',
                    'operationId' => 'loginUser',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/LoginRequest',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Login successful.',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/AuthResponse',
                                    ],
                                ],
                            ],
                        ],
                        '422' => [
                            '$ref' => '#/components/responses/ValidationError',
                        ],
                    ],
                ],
            ],
            "/{$version}/auth/logout" => [
                'post' => [
                    'tags' => ['Authentication'],
                    'summary' => 'Log out the current user',
                    'operationId' => 'logoutUser',
                    'security' => [
                        [
                            'sanctum' => [],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Logged out successfully.',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/EmptySuccessResponse',
                                    ],
                                ],
                            ],
                        ],
                        '401' => [
                            '$ref' => '#/components/responses/Unauthorized',
                        ],
                    ],
                ],
            ],
            "/{$version}/auth/me" => [
                'get' => [
                    'tags' => ['Authentication'],
                    'summary' => 'Get the authenticated user',
                    'operationId' => 'getAuthenticatedUser',
                    'security' => [
                        [
                            'sanctum' => [],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Authenticated user retrieved.',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/UserResponse',
                                    ],
                                ],
                            ],
                        ],
                        '401' => [
                            '$ref' => '#/components/responses/Unauthorized',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     tags: array<int, array{name: string, description: string}>,
     *     paths: array<string, mixed>
     * }
     */
    private function resourceRouteDocumentation(string $apiPrefix): array
    {
        $tags = [];
        $paths = [];

        foreach (Route::getRoutes() as $route) {
            $action = $this->routeAction($route);

            if (! in_array($action, self::RESOURCE_ACTIONS, true)) {
                continue;
            }

            $path = $this->routePath($route, $apiPrefix);

            if ($path === null) {
                continue;
            }

            $resource = $this->resourceNameFromPath($path);
            $tag = Str::headline($resource);
            $tags[$tag] = [
                'name' => $tag,
                'description' => "{$tag} resource endpoints.",
            ];

            foreach ($this->httpMethods($route) as $method) {
                $paths[$path][$method] = $this->resourceOperation($route, $method, $action, $resource, $tag);
            }
        }

        return [
            'tags' => array_values($tags),
            'paths' => $paths,
        ];
    }

    private function routeAction(LaravelRoute $route): ?string
    {
        $controller = $route->getAction('controller');

        if (! is_string($controller) || ! str_contains($controller, '@')) {
            return null;
        }

        return Str::afterLast($controller, '@');
    }

    private function routePath(LaravelRoute $route, string $apiPrefix): ?string
    {
        $uri = trim($route->uri(), '/');

        if ($apiPrefix !== '') {
            if (! Str::startsWith($uri, $apiPrefix.'/')) {
                return null;
            }

            $uri = Str::after($uri, $apiPrefix.'/');
        }

        $path = preg_replace('/\{([^}]+)\?\}/', '{$1}', $uri) ?: $uri;

        return '/'.trim($path, '/');
    }

    /**
     * @return array<int, string>
     */
    private function httpMethods(LaravelRoute $route): array
    {
        return collect($route->methods())
            ->map(fn (string $method): string => Str::lower($method))
            ->reject(fn (string $method): bool => in_array($method, ['head', 'options'], true))
            ->values()
            ->all();
    }

    private function resourceNameFromPath(string $path): string
    {
        return collect(explode('/', trim($path, '/')))
            ->reject(fn (string $segment): bool => str_starts_with($segment, '{'))
            ->last() ?? 'resource';
    }

    /**
     * @return array<string, mixed>
     */
    private function resourceOperation(LaravelRoute $route, string $method, string $action, string $resource, string $tag): array
    {
        $operation = [
            'tags' => [$tag],
            'summary' => $this->resourceSummary($action, $resource),
            'operationId' => Str::camel($resource.'_'.$action),
            'parameters' => $this->pathParameters($route),
            'responses' => $this->resourceResponses($action),
        ];

        if (in_array($method, ['post', 'put', 'patch'], true)) {
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'additionalProperties' => true,
                        ],
                    ],
                ],
            ];
        }

        if ($this->usesBearerAuthentication($route)) {
            $operation['security'] = [
                [
                    'sanctum' => [],
                ],
            ];
        }

        return $operation;
    }

    private function resourceSummary(string $action, string $resource): string
    {
        $label = Str::headline($resource);
        $singular = Str::singular($label);

        return match ($action) {
            'index' => "List {$label}",
            'store' => "Create {$singular}",
            'show' => "Get {$singular}",
            'update' => "Update {$singular}",
            'destroy' => "Delete {$singular}",
            default => Str::headline($action.' '.$resource),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pathParameters(LaravelRoute $route): array
    {
        preg_match_all('/\{([^}]+)\??\}/', $route->uri(), $matches);

        return collect($matches[1] ?? [])
            ->map(fn (string $parameter): array => [
                'name' => $parameter,
                'in' => 'path',
                'required' => true,
                'schema' => [
                    'type' => 'string',
                ],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function resourceResponses(string $action): array
    {
        if ($action === 'destroy') {
            return [
                '204' => [
                    'description' => 'Resource deleted successfully.',
                ],
                '404' => [
                    '$ref' => '#/components/responses/NotFound',
                ],
            ];
        }

        return [
            in_array($action, ['store'], true) ? '201' : '200' => [
                'description' => 'Successful response.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => $action === 'index'
                                ? '#/components/schemas/GenericCollectionResponse'
                                : '#/components/schemas/GenericResourceResponse',
                        ],
                    ],
                ],
            ],
            '404' => [
                '$ref' => '#/components/responses/NotFound',
            ],
            '422' => [
                '$ref' => '#/components/responses/ValidationError',
            ],
        ];
    }

    private function usesBearerAuthentication(LaravelRoute $route): bool
    {
        return collect($route->gatherMiddleware())
            ->contains(fn (string $middleware): bool => Str::startsWith($middleware, ['auth', 'auth:']));
    }

    /**
     * @return array<string, mixed>
     */
    private function components(): array
    {
        return [
            'securitySchemes' => [
                'sanctum' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'Sanctum API token',
                ],
            ],
            'responses' => [
                'ValidationError' => [
                    'description' => 'Validation failed.',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ErrorResponse',
                            ],
                        ],
                    ],
                ],
                'Unauthorized' => [
                    'description' => 'Authentication token is missing or invalid.',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ErrorResponse',
                            ],
                        ],
                    ],
                ],
                'NotFound' => [
                    'description' => 'Resource was not found.',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ErrorResponse',
                            ],
                        ],
                    ],
                ],
            ],
            'schemas' => [
                'ApiMetadata' => [
                    'type' => 'object',
                    'required' => ['name', 'version'],
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'example' => config('api.name'),
                        ],
                        'version' => [
                            'type' => 'string',
                            'example' => config('api.version'),
                        ],
                    ],
                ],
                'ApiMetadataResponse' => $this->successEnvelope([
                    '$ref' => '#/components/schemas/ApiMetadata',
                ], 'API is ready.'),
                'AuthResponse' => $this->successEnvelope([
                    '$ref' => '#/components/schemas/AuthPayload',
                ], 'Login successful.'),
                'UserResponse' => $this->successEnvelope([
                    '$ref' => '#/components/schemas/User',
                ], 'Authenticated user retrieved.'),
                'EmptySuccessResponse' => $this->successEnvelope([
                    'type' => 'object',
                ], 'Logged out successfully.'),
                'GenericCollectionResponse' => $this->successEnvelope([
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                    ],
                ], 'Operation completed successfully.'),
                'GenericResourceResponse' => $this->successEnvelope([
                    'type' => 'object',
                    'additionalProperties' => true,
                ], 'Operation completed successfully.'),
                'AuthPayload' => [
                    'type' => 'object',
                    'required' => ['token', 'token_type', 'user'],
                    'properties' => [
                        'token' => [
                            'type' => 'string',
                            'example' => '1|plain-text-token',
                        ],
                        'token_type' => [
                            'type' => 'string',
                            'example' => 'Bearer',
                        ],
                        'user' => [
                            '$ref' => '#/components/schemas/User',
                        ],
                    ],
                ],
                'User' => [
                    'type' => 'object',
                    'required' => ['id', 'name', 'email'],
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'example' => 1,
                        ],
                        'name' => [
                            'type' => 'string',
                            'example' => 'Jane Doe',
                        ],
                        'email' => [
                            'type' => 'string',
                            'format' => 'email',
                            'example' => 'jane@example.com',
                        ],
                        'email_verified_at' => [
                            'type' => ['string', 'null'],
                            'format' => 'date-time',
                        ],
                        'created_at' => [
                            'type' => ['string', 'null'],
                            'format' => 'date-time',
                        ],
                        'updated_at' => [
                            'type' => ['string', 'null'],
                            'format' => 'date-time',
                        ],
                    ],
                ],
                'RegisterRequest' => [
                    'type' => 'object',
                    'required' => ['name', 'email', 'password', 'password_confirmation'],
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'example' => 'Jane Doe',
                        ],
                        'email' => [
                            'type' => 'string',
                            'format' => 'email',
                            'maxLength' => 255,
                            'example' => 'jane@example.com',
                        ],
                        'password' => [
                            'type' => 'string',
                            'format' => 'password',
                            'minLength' => 8,
                            'example' => 'secret123',
                        ],
                        'password_confirmation' => [
                            'type' => 'string',
                            'format' => 'password',
                            'minLength' => 8,
                            'example' => 'secret123',
                        ],
                    ],
                ],
                'LoginRequest' => [
                    'type' => 'object',
                    'required' => ['email', 'password'],
                    'properties' => [
                        'email' => [
                            'type' => 'string',
                            'format' => 'email',
                            'example' => 'jane@example.com',
                        ],
                        'password' => [
                            'type' => 'string',
                            'format' => 'password',
                            'example' => 'secret123',
                        ],
                    ],
                ],
                'ErrorResponse' => [
                    'type' => 'object',
                    'required' => ['success', 'message', 'errors', 'code'],
                    'properties' => [
                        'success' => [
                            'type' => 'boolean',
                            'example' => false,
                        ],
                        'message' => [
                            'type' => 'string',
                            'example' => 'The request failed.',
                        ],
                        'errors' => [
                            'type' => 'object',
                            'additionalProperties' => true,
                        ],
                        'code' => [
                            'type' => 'string',
                            'example' => 'VALIDATION_ERROR',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $dataSchema
     * @return array<string, mixed>
     */
    private function successEnvelope(array $dataSchema, string $message): array
    {
        return [
            'type' => 'object',
            'required' => ['success', 'message', 'data'],
            'properties' => [
                'success' => [
                    'type' => 'boolean',
                    'example' => true,
                ],
                'message' => [
                    'type' => 'string',
                    'example' => $message,
                ],
                'data' => $dataSchema,
            ],
        ];
    }

    /**
     * @param  array<mixed>  $value
     */
    private function yaml(array $value, int $indent = 0): string
    {
        $lines = [];
        $spaces = str_repeat(' ', $indent);

        foreach ($value as $key => $item) {
            if (is_array($item) && $item !== []) {
                if (Arr::isList($item)) {
                    $lines[] = "{$spaces}{$key}:";
                    $lines[] = $this->yamlList($item, $indent + 2);
                } else {
                    $lines[] = "{$spaces}{$key}:";
                    $lines[] = $this->yaml($item, $indent + 2);
                }
            } else {
                $lines[] = "{$spaces}{$key}: ".$this->yamlScalar($item);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<mixed>  $value
     */
    private function yamlList(array $value, int $indent): string
    {
        $lines = [];
        $spaces = str_repeat(' ', $indent);

        foreach ($value as $item) {
            if (is_array($item) && $item !== []) {
                if (Arr::isList($item)) {
                    $lines[] = "{$spaces}-";
                    $lines[] = $this->yamlList($item, $indent + 2);
                } else {
                    $lines[] = "{$spaces}-";
                    $lines[] = $this->yaml($item, $indent + 2);
                }
            } else {
                $lines[] = "{$spaces}- ".$this->yamlScalar($item);
            }
        }

        return implode("\n", $lines);
    }

    private function yamlScalar(mixed $value): string
    {
        if ($value === []) {
            return '[]';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return json_encode((string) $value, JSON_THROW_ON_ERROR);
    }
}
