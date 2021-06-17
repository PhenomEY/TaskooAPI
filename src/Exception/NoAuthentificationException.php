<?php declare(strict_types=1);

namespace Taskoo\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NoAuthentificationException extends HttpException
{
    public function __construct()
    {
        parent::__construct(Response::HTTP_FORBIDDEN, 'No authentication provided');
    }

    public function getErrorCode(): string
    {
        return 'no_authentication_provided';
    }
}