<?php declare(strict_types=1);

namespace Taskoo\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ContainsFilesException extends HttpException
{
    public function __construct()
    {
        parent::__construct(Response::HTTP_FORBIDDEN, 'contains_files');
    }

    public function getErrorCode(): string
    {
        return 'contains_files';
    }
}