<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class ForbiddenException extends ApiException
{
    /**
     * @param  array<string, mixed>  $errors
     */
    public function __construct(string $message = 'This action is unauthorized.', array $errors = [], string $code = 'FORBIDDEN')
    {
        parent::__construct($message, Response::HTTP_FORBIDDEN, $code, $errors);
    }
}
