<?php declare(strict_types=1);

namespace Ellipse\Session;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;

use Dflydev\FigCookies\SetCookie;
use Dflydev\FigCookies\FigResponseCookies;

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
        session_start([
            'use_cookies' => false,
            'use_only_cookies' => true,
        ]);

        $response = $handler->handle($request);

        $session_id = session_id();

        session_write_close();

        $cookie = $this->sessionCookie($session_id);

        return FigResponseCookies::set($response, $cookie);
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
     * Return a cookie containing the given session id.
     *
     * @param string $session_id
     * @return \Dflydev\FigCookies\SetCookie
     */
    private function sessionCookie(string $session_id): SetCookie
    {
        $cookie = $this->cookieOptions();

        $cookie_name = $cookie['name'];
        $cookie_lifetime = $cookie['lifetime'];
        $cookie_path = $cookie['path'];
        $cookie_domain = $cookie['domain'];
        $secure = $cookie['secure'];
        $httponly = $cookie['httponly'];

        $timestamp = $cookie_lifetime <= 0 ? 0 : time() + $cookie_lifetime;

        return SetCookie::create($cookie_name, $session_id)
            ->withExpires($timestamp)
            ->withMaxAge($timestamp)
            ->withPath($cookie_path)
            ->withDomain($cookie_domain)
            ->withSecure($secure)
            ->withHttpOnly($httponly);
    }
}
