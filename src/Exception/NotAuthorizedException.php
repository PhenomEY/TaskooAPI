<?php declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NotAuthorizedException extends HttpException
{
    public function __construct()
    {
        parent::__construct(Response::HTTP_FORBIDDEN, 'User not Authorized.');
    }

    public function getErrorCode(): string
    {
        return 'permission_denied';
    }
}