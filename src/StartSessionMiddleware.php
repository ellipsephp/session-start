<?php declare(strict_types=1);

namespace Ellipse\Session;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Dflydev\FigCookies\SetCookie;
use Dflydev\FigCookies\FigResponseCookies;

use Ellipse\Session\Exceptions\SessionStartException;
use Ellipse\Session\Exceptions\SessionDisabledException;
use Ellipse\Session\Exceptions\SessionAlreadyStartedException;
use Ellipse\Session\Exceptions\SessionAlreadyClosedException;

class StartSessionMiddleware implements MiddlewareInterface
{
    /**
     * Session options disabling php session cookie handling.
     *
     * @var array
     */
    const SESSION_OPTIONS = [
        'use_trans_sid' => false,
        'use_cookies' => false,
        'use_only_cookies' => true,
    ];

    /**
     * The session cookie name.
     *
     * @var string
     */
    private $name;

    /**
     * The session cookie options.
     *
     * @var array
     */
    private $options;

    /**
     * Set up a start session middleware with the given cookie name and options.
     *
     * @param string    $name
     * @param array     $options
     */
    public function __construct(string $name = 'ellipse_session', array $options = [])
    {
        $this->name = $name;
        $this->options = $options;
    }

    /**
     * Start the session, handle the request and add the session cookie to the
     * response.
     *
     * @param \Psr\Http\Message\ServerRequestInterface  $request
     * @param \Psr\Http\Server\RequestHandlerInterface  $handler
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Ellipse\Session\Exceptions\SessionStartException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Fail when session is disabled or has already started.
        $this->failWhenDisabled();
        $this->failWhenStarted();

        // Try to retrieve the session id from the request cookies.
        $cookies = $request->getCookieParams();

        $session_id = $cookies[$this->name] ?? '';

        if ($session_id != '') session_id($session_id);

        // Handle the request when session_start is successful.
        if (session_start(self::SESSION_OPTIONS)) {

            session_name($this->name);

            $response = $handler->handle($request);

            $this->failWhenClosed();

            $session_id = session_id();

            session_write_close();

            return $this->withSessionCookie($response, $session_id);

        }

        throw new SessionStartException;
    }

    /**
     * Fail when the session is disabled.
     *
     * @return void
     * @throws \Ellipse\Session\Exceptions\SessionDisabledException
     */
    private function failWhenDisabled()
    {
        if (session_status() === PHP_SESSION_DISABLED) {

            throw new SessionDisabledException;

        }
    }

    /**
     * Fail when the session has already started.
     *
     * @return void
     * @throws \Ellipse\Session\Exceptions\SessionAlreadyStartedException
     */
    private function failWhenStarted()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {

            throw new SessionAlreadyStartedException;

        }
    }

    /**
     * Fail when the session has already been closed.
     *
     * @return void
     * @throws \Ellipse\Session\Exceptions\SessionAlreadyClosedException
     */
    private function failWhenClosed()
    {
        if (session_status() === PHP_SESSION_NONE) {

            throw new SessionAlreadyClosedException;

        }
    }

    /**
     * Attach a session cookie with the given sesison id to the given response.
     *
     * @param \Psr\Http\Message\ResponseInterface   $response
     * @param string                                $session_id
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function withSessionCookie(ResponseInterface $response, string $session_id): ResponseInterface
    {
        // Merge session cookie options.
        $default = session_get_cookie_params();

        $default = array_change_key_case($default, CASE_LOWER);
        $options = array_change_key_case($this->options, CASE_LOWER);

        $options = array_merge($default, $options);

        if ($options['lifetime'] < 0) $options['lifetime'] = 0;

        // Create a session cookie and attach it to the response.
        $cookie = SetCookie::create($this->name, $session_id)
            ->withMaxAge($options['lifetime'])
            ->withPath($options['path'])
            ->withDomain($options['domain'])
            ->withSecure($options['secure'])
            ->withHttpOnly($options['httponly']);

        if ($options['lifetime'] > 0) {

            $cookie = $cookie->withExpires(time() + $options['lifetime']);

        }

        return FigResponseCookies::set($response, $cookie);
    }
}
