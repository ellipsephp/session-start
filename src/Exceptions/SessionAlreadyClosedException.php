<?php declare(strict_types=1);

namespace Ellipse\Session\Exceptions;

use RuntimeException;

class SessionAlreadyClosedException extends RuntimeException implements SessionStartExceptionInterface
{
    public function __construct()
    {
        parent::__construct('Session is already closed: session must not be manually closed to use StartSessionMiddleware');
    }
}
