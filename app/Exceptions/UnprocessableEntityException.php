<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class UnprocessableEntityException extends ApiException
{
    /**
     * @param  array<string, mixed>  $errors
     */
    public function __construct(string $message = 'The given data was invalid.', array $errors = [], string $code = 'VALIDATION_ERROR')
    {
        parent::__construct($message, Response::HTTP_UNPROCESSABLE_ENTITY, $code, $errors);
    }
}
