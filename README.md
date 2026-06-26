# Larapi Core

Larapi Core is a lightweight API-only Laravel starter kit. It ships with JSON-first defaults, Sanctum bearer token auth, standardized API responses, and an internal generator for API resources.

No Blade views, no Vite setup, no web routes, and no session-first assumptions.

## Included

- API-only routing through `routes/api.php`
- Versioned auth endpoints under `/api/v1/auth`
- Sanctum bearer token authentication
- Consistent JSON response envelope
- JSON-first exception handling for validation, auth, not-found, authorization, and rate-limit errors
- Custom API exceptions for domain and business errors
- Configurable API metadata in `config/api.php`
- Swagger UI and OpenAPI documentation endpoints
- Lightweight local defaults: SQLite, file cache, sync queue
- `make:api` scaffolding for controllers, services, requests, resources, tests, DTOs, models, and migrations

## Requirements

- PHP 8.3+
- Composer
- SQLite by default, or another Laravel-supported database

## Quick Start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Or:

```bash
composer setup
php artisan serve
```

## API Defaults

```dotenv
API_NAME="${APP_NAME}"
API_VERSION=v1
API_RATE_LIMIT=60,1
API_DOCS_ENABLED=true
API_DOCS_PATH=docs
QUEUE_CONNECTION=sync
CACHE_STORE=file
```

`API_RATE_LIMIT=60,1` means 60 requests per 1 minute.

## Endpoints

| Method | Endpoint | Auth | Description |
| --- | --- | --- | --- |
| GET | `/api/v1/status` | No | API metadata and readiness |
| POST | `/api/v1/auth/register` | No | Create account and receive token |
| POST | `/api/v1/auth/login` | No | Authenticate and receive token |
| POST | `/api/v1/auth/logout` | Bearer | Revoke current token |
| GET | `/api/v1/auth/me` | Bearer | Get authenticated user |
| GET | `/docs` | No | Swagger UI API documentation |
| GET | `/api/docs` | No | Swagger UI API documentation |
| GET | `/api/docs/openapi.json` | No | OpenAPI JSON specification |
| GET | `/api/docs/openapi.yaml` | No | OpenAPI YAML specification |

## API Documentation

Larapi Core ships with Swagger-ready OpenAPI 3.1 documentation for the built-in status and auth endpoints.

```bash
php artisan serve
```

Open the interactive docs at:

```txt
http://localhost:8000/docs
```

The API-prefixed documentation URL is also available at `http://localhost:8000/api/docs`.

Generate a static specification file:

```bash
php artisan api:docs
php artisan api:docs --format=yaml
php artisan api:docs --output=storage/api-docs/openapi.json
```

Documentation settings live in `config/api.php`:

```dotenv
API_DOCS_ENABLED=true
API_DOCS_PATH=docs
API_DOCS_JSON_PATH=docs/openapi.json
API_DOCS_YAML_PATH=docs/openapi.yaml
API_DOCS_OUTPUT_PATH=storage/api-docs/openapi.json
API_DOCS_SERVER_URL=
API_DOCS_DESCRIPTION="Interactive Swagger documentation for the Larapi Core API."
API_DOCS_CONTACT_EMAIL=api@example.com
```

By default Swagger uses a relative server URL like `/api`, so "Try it out" requests use the same host and port as the documentation page. Set `API_DOCS_SERVER_URL=https://api.example.com/api` only when you want Swagger to call a fixed external API server.

Add custom endpoint documentation by merging OpenAPI fragments through `api.documentation.extensions` in `config/api.php`.

API resource routes are discovered automatically from Laravel's route table. For example:

```php
Route::apiResource('v1/users', UserController::class);
```

will add Swagger path operations for `/v1/users` and `/v1/users/{user}`.

For better Swagger UI request forms on `POST`, `PUT`, and `PATCH`, type-hint a Laravel `FormRequest` on your controller actions. Larapi Core reads the request `rules()` and converts common validation rules into OpenAPI fields:

```php
public function store(UserRequest $request): JsonResponse
{
    // ...
}

public function update(UserRequest $request, User $user): JsonResponse
{
    // ...
}
```

## Response Envelope

Success:

```json
{
  "success": true,
  "message": "Operation completed successfully.",
  "data": {}
}
```

Error:

```json
{
  "success": false,
  "message": "The request failed.",
  "errors": {},
  "code": "VALIDATION_ERROR"
}
```

Use `successResponse()` and `errorResponse()` from the base controller, or call `App\Http\Responses\ApiResponse` directly.

## Exception Handling

Larapi Core centralizes API exception rendering in `App\Exceptions\ApiExceptionHandler`.

Throw custom exceptions from controllers, services, jobs, or actions and they will be converted into the standard error envelope automatically:

```php
use App\Exceptions\ConflictException;

throw new ConflictException(
    message: 'Email address is already reserved.',
    errors: [
        'email' => ['already_reserved'],
    ],
);
```

Response:

```json
{
  "success": false,
  "message": "Email address is already reserved.",
  "errors": {
    "email": ["already_reserved"]
  },
  "code": "CONFLICT"
}
```

Available exceptions:

```txt
ApiException
BadRequestException
ConflictException
ForbiddenException
ResourceNotFoundException
UnprocessableEntityException
```

Laravel validation, authentication, authorization, not-found, method-not-allowed, and rate-limit exceptions are also rendered through the same JSON contract.

## Authentication Example

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Jane","email":"jane@example.com","password":"secret123","password_confirmation":"secret123"}'
```

```bash
curl http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Generator

Generate a full API resource:

```bash
php artisan make:api Post --all --model --migration --routes --api-version=v1
```

Useful combinations:

```bash
php artisan make:api Post --controller --service --request
php artisan make:api Post --model --migration
php artisan make:api Post --all --model --migration --force
```

Available flags:

```txt
--controller
--service
--request
--resource
--test
--dto
--model
--migration
--routes
--api-version=v1
--force
```

## Structure

```txt
app/
  Console/Commands/MakeApiCommand.php
  Console/Commands/GenerateApiDocsCommand.php
  Http/
    Controllers/
      Api/V1/AuthController.php
      Api/DocumentationController.php
      Controller.php
    Middleware/ForceJsonResponse.php
    Responses/ApiResponse.php
  Exceptions/
    ApiExceptionHandler.php
    ApiException.php
  Models/User.php
  Support/OpenApiSpecification.php
config/
  api.php
routes/
  api.php
stubs/
  api/
tests/
  Feature/
```

## Testing

```bash
composer test
```

or:

```bash
php artisan test
```

## License

MIT
