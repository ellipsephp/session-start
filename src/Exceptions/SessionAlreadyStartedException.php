<?php declare(strict_types=1);

namespace Ellipse\Session\Exceptions;

use RuntimeException;

class SessionAlreadyStartedException extends RuntimeException implements SessionStartExceptionInterface
{
    public function __construct()
    {
        parent::__construct('Session is already started: session must not be manually started to use StartSessionMiddleware');
    }
}
