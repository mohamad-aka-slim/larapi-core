<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class ConflictException extends ApiException
{
    /**
     * @param  array<string, mixed>  $errors
     */
    public function __construct(string $message = 'The request conflicts with the current state.', array $errors = [], string $code = 'CONFLICT')
    {
        parent::__construct($message, Response::HTTP_CONFLICT, $code, $errors);
    }
}
