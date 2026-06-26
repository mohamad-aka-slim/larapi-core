<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class ResourceNotFoundException extends ApiException
{
    /**
     * @param  array<string, mixed>  $errors
     */
    public function __construct(string $message = 'The requested resource was not found.', array $errors = [], string $code = 'NOT_FOUND')
    {
        parent::__construct($message, Response::HTTP_NOT_FOUND, $code, $errors);
    }
}
