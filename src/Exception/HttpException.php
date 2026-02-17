<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * HTTP Exception
 *
 * Represents an HTTP error response.
 */
class HttpException extends \Exception
{
    private int $statusCode;

    public function __construct(int $statusCode, string $message = '')
    {
        $this->statusCode = $statusCode;
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
