<?php declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidRequestException extends HttpException
{
    public function __construct()
    {
        parent::__construct(Response::HTTP_FORBIDDEN, 'Invalid request');
    }

    public function getErrorCode(): string
    {
        return 'invalid_request';
    }
}