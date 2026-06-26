<?php

namespace Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    public function test_openapi_json_endpoint_documents_core_api_routes(): void
    {
        $this->getJson('/api/docs/openapi.json')
            ->assertOk()
            ->assertJsonPath('openapi', '3.1.0')
            ->assertJsonPath('info.title', config('api.name'))
            ->assertJsonPath('servers.0.url', '/api')
            ->assertJsonPath('paths./v1/status.get.operationId', 'getApiStatus')
            ->assertJsonPath('paths./v1/auth/register.post.operationId', 'registerUser')
            ->assertJsonPath('components.securitySchemes.sanctum.scheme', 'bearer');
    }

    public function test_openapi_server_url_can_be_configured_for_external_api_hosts(): void
    {
        config(['api.documentation.server_url' => 'https://api.example.com/api']);

        $this->getJson('/api/docs/openapi.json')
            ->assertOk()
            ->assertJsonPath('servers.0.url', 'https://api.example.com/api');
    }

    public function test_openapi_yaml_endpoint_is_available(): void
    {
        $this->get('/api/docs/openapi.yaml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/yaml; charset=UTF-8')
            ->assertSee('openapi: "3.1.0"', false)
            ->assertSee('/v1/auth/login:', false);
    }

    public function test_swagger_ui_endpoint_loads_interactive_documentation(): void
    {
        $this->get('/api/docs')
            ->assertOk()
            ->assertSee('SwaggerUIBundle', false)
            ->assertSee('/api/docs/openapi.json', false);
    }

    public function test_root_swagger_ui_endpoint_loads_interactive_documentation(): void
    {
        $this->get('/docs')
            ->assertOk()
            ->assertSee('SwaggerUIBundle', false)
            ->assertSee('/api/docs/openapi.json', false);
    }

    public function test_root_swagger_ui_endpoint_does_not_start_database_sessions(): void
    {
        config(['session.driver' => 'database']);

        $this->get('/docs')
            ->assertOk()
            ->assertSee('SwaggerUIBundle', false);
    }

    public function test_openapi_json_includes_api_resource_routes_from_route_table(): void
    {
        Route::apiResource('api/v1/users', DocumentationProbeUserController::class);

        $this->getJson('/api/docs/openapi.json')
            ->assertOk()
            ->assertJsonPath('paths./v1/users.get.operationId', 'usersIndex')
            ->assertJsonPath('paths./v1/users.post.operationId', 'usersStore')
            ->assertJsonPath('paths./v1/users/{user}.get.operationId', 'usersShow')
            ->assertJsonPath('paths./v1/users/{user}.put.operationId', 'usersUpdate')
            ->assertJsonPath('paths./v1/users/{user}.patch.operationId', 'usersUpdate')
            ->assertJsonPath('paths./v1/users/{user}.delete.operationId', 'usersDestroy');
    }

    public function test_openapi_json_builds_request_body_schema_from_form_request_rules(): void
    {
        Route::apiResource('api/v1/users', DocumentationProbeUserController::class);

        $this->getJson('/api/docs/openapi.json')
            ->assertOk()
            ->assertJsonPath('paths./v1/users.post.requestBody.content.application/json.schema.$ref', '#/components/schemas/DocumentationProbeUserRequest')
            ->assertJsonPath('paths./v1/users/{user}.put.requestBody.content.application/json.schema.$ref', '#/components/schemas/DocumentationProbeUserRequest')
            ->assertJsonPath('paths./v1/users/{user}.patch.requestBody.content.application/json.schema.$ref', '#/components/schemas/DocumentationProbeUserRequest')
            ->assertJsonPath('components.schemas.DocumentationProbeUserRequest.required.0', 'name')
            ->assertJsonPath('components.schemas.DocumentationProbeUserRequest.required.1', 'email')
            ->assertJsonPath('components.schemas.DocumentationProbeUserRequest.required.2', 'role')
            ->assertJsonPath('components.schemas.DocumentationProbeUserRequest.required.3', 'password')
            ->assertJsonPath('components.schemas.DocumentationProbeUserRequest.required.4', 'password_confirmation')
            ->assertJsonPath('components.schemas.DocumentationProbeUserRequest.properties.name.type', 'string')
            ->assertJsonPath('components.schemas.DocumentationProbeUserRequest.properties.name.maxLength', 255)
            ->assertJsonPath('components.schemas.DocumentationProbeUserRequest.properties.email.format', 'email')
            ->assertJsonPath('components.schemas.DocumentationProbeUserRequest.properties.age.type.0', 'integer')
            ->assertJsonPath('components.schemas.DocumentationProbeUserRequest.properties.age.type.1', 'null')
            ->assertJsonPath('components.schemas.DocumentationProbeUserRequest.properties.age.minimum', 18)
            ->assertJsonPath('components.schemas.DocumentationProbeUserRequest.properties.role.enum.0', 'admin')
            ->assertJsonPath('components.schemas.DocumentationProbeUserRequest.properties.role.enum.1', 'user')
            ->assertJsonPath('components.schemas.DocumentationProbeUserRequest.properties.password.format', 'password');
    }

    public function test_api_docs_command_generates_json_file(): void
    {
        $files = new Filesystem;
        $path = storage_path('framework/testing/openapi.json');

        if ($files->exists($path)) {
            $files->delete($path);
        }

        $this->artisan('api:docs', ['--output' => $path])
            ->assertSuccessful();

        $this->assertFileExists($path);
        $this->assertStringContainsString('"openapi": "3.1.0"', $files->get($path));

        $files->delete($path);
    }
}

class DocumentationProbeUserController
{
    public function index(): void {}

    public function store(DocumentationProbeUserRequest $request): void {}

    public function show(): void {}

    public function update(DocumentationProbeUserRequest $request): void {}

    public function destroy(): void {}
}

class DocumentationProbeUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => 'required|email|max:255',
            'age' => ['nullable', 'integer', 'min:18'],
            'role' => ['required', 'in:admin,user'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
