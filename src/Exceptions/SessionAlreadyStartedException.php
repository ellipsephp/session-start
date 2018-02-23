<?php declare(strict_types=1);

namespace Ellipse\Session\Exceptions;

use RuntimeException;

class SessionAlreadyStartedException extends RuntimeException implements SessionStartExceptionInterface
{
    public function __construct()
    {
        $msg = "Session is already started. Session must not be manually started in order to use StartSessionMiddleware.";

        parent::__construct($msg);
    }
}
