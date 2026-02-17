<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Forbidden Exception (403)
 */
class ForbiddenException extends HttpException
{
    public function __construct(string $message = 'Access denied')
    {
        parent::__construct(403, $message);
    }
}
