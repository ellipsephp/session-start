<?php declare(strict_types=1);

namespace Ellipse\Session;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;

use Dflydev\FigCookies\SetCookie;
use Dflydev\FigCookies\FigResponseCookies;

use Ellipse\Session\Exceptions\SessionsDisabledException;
use Ellipse\Session\Exceptions\SessionAlreadyStartedException;
use Ellipse\Session\Exceptions\SessionAlreadyClosedException;

class StartSessionMiddleware implements MiddlewareInterface
{
    /**
     * The session cookie options.
     *
     * @var array
     */
    private $cookie;

    /**
     * Set up a start session middleware with the given cookie options.
     *
     * @param array $cookie
     */
    public function __construct(array $cookie = [])
    {
        $this->cookie = $cookie;
    }

    /**
     * Start the session, delegate the request processing and add the session
     * cookie to the response.
     *
     * @param \Psr\Http\Message\ServerRequestInterface      $request
     * @param \Interop\Http\Server\RequestHandlerInterface  $handler
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->failWhenDisabled();
        $this->failWhenStarted();

        // Set the session id from the request cookies when present.
        $options = $this->cookieOptions();

        $session_id = $this->sessionId($request, $options);

        if ($session_id != '') session_id($session_id);

        // Start the session and delegate the request processing.
        session_start(['use_cookies' => false, 'use_only_cookies' => true]);

        $response = $handler->handle($request);

        // Save the session and return a response with the session cookie.
        $this->failWhenClosed();

        $session_id = session_id();

        session_write_close();

        return $this->withSessionCookie($response, $session_id, $options);
    }

    /**
     * Fail when the sessions are disabled.
     *
     * @return void
     * @throws \Ellipse\Session\Exceptions\SessionsDisabledException
     */
    private function failWhenDisabled(): void
    {
        if (session_status() === PHP_SESSION_DISABLED) {

            throw new SessionsDisabledException;

        }
    }

    /**
     * Fail when the session has already started.
     *
     * @return void
     * @throws \Ellipse\Session\Exceptions\SessionAlreadyStartedException
     */
    private function failWhenStarted(): void
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
    private function failWhenClosed(): void
    {
        if (session_status() === PHP_SESSION_NONE) {

            throw new SessionAlreadyClosedException;

        }
    }

    /**
     * Return the session cookie options merged with the default ones.
     *
     * @return array
     */
    private function cookieOptions(): array
    {
        $default = session_get_cookie_params();
        $default['name'] = session_name();

        $default = array_change_key_case($default, CASE_LOWER);
        $cookie = array_change_key_case($this->cookie, CASE_LOWER);

        return array_merge($default, $cookie);
    }

    /**
     * Return the session id from the given request. Default to empty string.
     *
     * @param string    $session_id
     * @param array     $options
     * @return string
     */
    private function sessionId(ServerRequestInterface $request, array $options): string
    {
        $name = $options['name'];

        $cookies = $request->getCookieParams();

        return $cookies[$name] ?? '';
    }

    /**
     * Return new response based on the given response with a cookie based on
     * the given session id and cookie options.
     *
     * @param \Psr\Http\Message\ResponseInterface   $response
     * @param string                                $session_id
     * @param array                                 $options
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function withSessionCookie(ResponseInterface $response, string $session_id, array $options): ResponseInterface
    {
        $cookie_name = $options['name'];
        $cookie_lifetime = $options['lifetime'];
        $cookie_path = $options['path'];
        $cookie_domain = $options['domain'];
        $secure = $options['secure'];
        $httponly = $options['httponly'];

        $timestamp = $cookie_lifetime <= 0 ? 0 : time() + $cookie_lifetime;

        $cookie = SetCookie::create($cookie_name, $session_id)
            ->withExpires($timestamp)
            ->withMaxAge($timestamp)
            ->withPath($cookie_path)
            ->withDomain($cookie_domain)
            ->withSecure($secure)
            ->withHttpOnly($httponly);

        return FigResponseCookies::set($response, $cookie);
    }
}
