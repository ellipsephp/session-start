<?php declare(strict_types=1);

namespace Ellipse\Session\Exceptions;

use RuntimeException;

class SessionDisabledException extends RuntimeException implements SessionStartExceptionInterface
{
    public function __construct()
    {
        parent::__construct('Session is disabled: session must not be disabled in order to use StartSessionMiddleware');
    }
}
