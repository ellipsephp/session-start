<?php declare(strict_types=1);

namespace Ellipse\Session\Exceptions;

use RuntimeException;

class SessionStartException extends RuntimeException implements SessionStartExceptionInterface
{
    public function __construct()
    {
        parent::__construct('Unable to start a session: session_start() returned false');
    }
}
