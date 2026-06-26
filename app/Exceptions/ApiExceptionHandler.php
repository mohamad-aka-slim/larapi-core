<?php

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class ApiExceptionHandler
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(fn (ApiException $exception): JsonResponse => ApiResponse::error(
            message: $exception->getMessage(),
            status: $exception->status(),
            errors: $exception->errors(),
            code: $exception->errorCode(),
        ));

        $exceptions->render(fn (ValidationException $exception): JsonResponse => ApiResponse::error(
            message: 'The given data was invalid.',
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            errors: $exception->errors(),
            code: 'VALIDATION_ERROR',
        ));

        $exceptions->render(fn (AuthenticationException $exception): JsonResponse => ApiResponse::error(
            message: 'Unauthenticated.',
            status: Response::HTTP_UNAUTHORIZED,
            code: 'UNAUTHENTICATED',
        ));

        $exceptions->render(fn (AuthorizationException $exception): JsonResponse => ApiResponse::error(
            message: 'This action is unauthorized.',
            status: Response::HTTP_FORBIDDEN,
            code: 'FORBIDDEN',
        ));

        $exceptions->render(fn (ModelNotFoundException|NotFoundHttpException $exception): JsonResponse => ApiResponse::error(
            message: 'The requested resource was not found.',
            status: Response::HTTP_NOT_FOUND,
            code: 'NOT_FOUND',
        ));

        $exceptions->render(fn (MethodNotAllowedHttpException $exception): JsonResponse => ApiResponse::error(
            message: 'The requested method is not allowed for this route.',
            status: Response::HTTP_METHOD_NOT_ALLOWED,
            errors: [
                'method' => [strtoupper(request()->getMethod())],
            ],
            code: 'METHOD_NOT_ALLOWED',
        ));

        $exceptions->render(fn (TooManyRequestsHttpException $exception): JsonResponse => ApiResponse::error(
            message: 'Too many requests.',
            status: Response::HTTP_TOO_MANY_REQUESTS,
            code: 'TOO_MANY_REQUESTS',
        ));
    }
}
