<?php declare(strict_types=1);

namespace Ellipse\Session\Exceptions;

use RuntimeException;

class SessionsDisabledException extends RuntimeException implements SessionStartExceptionInterface
{
    public function __construct()
    {
        $msg = "Sessions are disabled. Sessions must not be disabled in order to use StartSessionMiddleware.";

        parent::__construct($msg);
    }
}
