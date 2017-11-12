<?php declare(strict_types=1);

namespace Ellipse\Session\Exceptions;

use RuntimeException;

class SessionAlreadyClosedException extends RuntimeException
{
    public function __construct()
    {
        $msg = "Session is already closed. Session must not be manually closed in order to use StartSessionMiddleware.";

        parent::__construct($msg);
    }
}
