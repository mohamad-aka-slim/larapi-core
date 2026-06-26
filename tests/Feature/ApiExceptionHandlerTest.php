<?php

namespace Tests\Feature;

use App\Exceptions\ApiException;
use App\Exceptions\ConflictException;
use App\Exceptions\ResourceNotFoundException;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ApiExceptionHandlerTest extends TestCase
{
    public function test_custom_api_exception_returns_standard_error_envelope(): void
    {
        Route::get('/api/testing/custom-exception', fn () => throw new ApiException(
            message: 'Payment provider rejected the request.',
            status: Response::HTTP_BAD_GATEWAY,
            errorCode: 'PAYMENT_PROVIDER_FAILED',
            errors: [
                'provider' => ['stripe'],
            ],
        ));

        $this->getJson('/api/testing/custom-exception')
            ->assertStatus(Response::HTTP_BAD_GATEWAY)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Payment provider rejected the request.')
            ->assertJsonPath('code', 'PAYMENT_PROVIDER_FAILED')
            ->assertJsonPath('errors.provider.0', 'stripe');
    }

    public function test_domain_exception_subclasses_have_expected_status_and_code(): void
    {
        Route::get('/api/testing/conflict-exception', fn () => throw new ConflictException(
            message: 'Email address is already reserved.',
            errors: [
                'email' => ['already_reserved'],
            ],
        ));

        $this->getJson('/api/testing/conflict-exception')
            ->assertConflict()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Email address is already reserved.')
            ->assertJsonPath('code', 'CONFLICT')
            ->assertJsonPath('errors.email.0', 'already_reserved');
    }

    public function test_resource_not_found_exception_uses_not_found_contract(): void
    {
        Route::get('/api/testing/missing-resource', fn () => throw new ResourceNotFoundException(
            message: 'Workspace was not found.',
            errors: [
                'workspace' => ['missing'],
            ],
        ));

        $this->getJson('/api/testing/missing-resource')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Workspace was not found.')
            ->assertJsonPath('code', 'NOT_FOUND')
            ->assertJsonPath('errors.workspace.0', 'missing');
    }

    public function test_method_not_allowed_returns_standard_error_envelope(): void
    {
        Route::get('/api/testing/method-only', fn () => response()->json());

        $this->postJson('/api/testing/method-only')
            ->assertStatus(Response::HTTP_METHOD_NOT_ALLOWED)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'METHOD_NOT_ALLOWED')
            ->assertJsonPath('errors.method.0', 'POST');
    }
}
