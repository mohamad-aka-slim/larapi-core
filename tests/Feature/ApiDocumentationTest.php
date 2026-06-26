<?php

namespace Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    public function test_openapi_json_endpoint_documents_core_api_routes(): void
    {
        $this->getJson('/api/docs/openapi.json')
            ->assertOk()
            ->assertJsonPath('openapi', '3.1.0')
            ->assertJsonPath('info.title', config('api.name'))
            ->assertJsonPath('paths./v1/status.get.operationId', 'getApiStatus')
            ->assertJsonPath('paths./v1/auth/register.post.operationId', 'registerUser')
            ->assertJsonPath('components.securitySchemes.sanctum.scheme', 'bearer');
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
