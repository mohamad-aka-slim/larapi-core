<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class BadRequestException extends ApiException
{
    /**
     * @param  array<string, mixed>  $errors
     */
    public function __construct(string $message = 'Bad request.', array $errors = [], string $code = 'BAD_REQUEST')
    {
        parent::__construct($message, Response::HTTP_BAD_REQUEST, $code, $errors);
    }
}
