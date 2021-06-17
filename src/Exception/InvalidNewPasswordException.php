<?php declare(strict_types=1);

namespace Taskoo\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidNewPasswordException extends HttpException
{
    public function __construct()
    {
        parent::__construct(Response::HTTP_FORBIDDEN, 'invalid_new_password');
    }

    public function getErrorCode(): string
    {
        return 'invalid_new_password';
    }
}