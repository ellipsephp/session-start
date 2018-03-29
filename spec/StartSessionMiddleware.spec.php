<?php

use function Eloquent\Phony\Kahlan\mock;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Zend\Diactoros\Response\TextResponse;

use Ellipse\Session\StartSessionMiddleware;
use Ellipse\Session\Exceptions\SessionStartException;
use Ellipse\Session\Exceptions\SessionDisabledException;
use Ellipse\Session\Exceptions\SessionAlreadyStartedException;
use Ellipse\Session\Exceptions\SessionAlreadyClosedException;

describe('StartSessionMiddleware', function () {

    beforeEach(function () {

        $this->middleware = new StartSessionMiddleware;

    });

    it('should implement MiddlewareInterface', function () {

        expect($this->middleware)->toBeAnInstanceOf(MiddlewareInterface::class);

    });

    describe('->process()', function () {

        beforeEach(function () {

            $statuses = [PHP_SESSION_NONE, PHP_SESSION_NONE, PHP_SESSION_ACTIVE];

            allow('session_status')->toBeCalled()->andReturn(...$statuses);
            allow('session_name')->toBeCalled()->andReturn('default_cookie_name');
            allow('session_get_cookie_params')->toBeCalled()->andReturn([
                'path' => '/default/path',
                'domain' => 'default.domain.com',
                'lifetime' => 3600,
                'secure' => false,
                'httponly' => false,
            ]);

            $this->request = mock(ServerRequestInterface::class);
            $this->response = new TextResponse('body', 404, ['set-cookie' => 'test=value']);

            $this->handler = mock(RequestHandlerInterface::class);

            $this->handler->handle->returns($this->response);

        });

        context('when session_start() returns true', function () {

            beforeEach(function () {

                $options = StartSessionMiddleware::SESSION_OPTIONS;

                allow('session_start')->toBeCalled()->with($options)->andReturn(true);

            });

            it('should return a response', function () {

                $test = $this->middleware->process($this->request->get(), $this->handler->get());

                expect($test)->toBeAnInstanceOf(ResponseInterface::class);

            });

            it('should call the request handler ->handle() method with the request', function () {

                $this->middleware->process($this->request->get(), $this->handler->get());

                $this->handler->handle->calledWith($this->request);

            });

            it('should return a response with the same body as the one returned by the request handler', function () {

                $test = $this->middleware->process($this->request->get(), $this->handler->get())
                    ->getBody()
                    ->getContents();

                expect($test)->toEqual('body');

            });

            it('should return a response with the same status code as the one returned by the request handler', function () {

                $test = $this->middleware->process($this->request->get(), $this->handler->get())
                    ->getStatusCode();

                expect($test)->toEqual(404);

            });

            it('should return a response with the same headers as the one returned by the request handler', function () {

                $test = $this->middleware->process($this->request->get(), $this->handler->get());

                expect($test->getHeaderLine('Content-type'))->toContain('text');
                expect($test->getHeaderLine('Set-cookie'))->toContain('test=value');

            });

            context('when the sessions are disabled', function () {

                it('should throw a SessionDisabledException', function () {

                    allow('session_status')->toBeCalled()->andReturn(PHP_SESSION_DISABLED);

                    $test = function () {

                        $this->middleware->process($this->request->get(), $this->handler->get());

                    };

                    $exception = new SessionDisabledException;

                    expect($test)->toThrow($exception);

                });

            });

            context('when the session is already started', function () {

                it('should throw a SessionAlreadyStartedException', function () {

                    allow('session_status')->toBeCalled()->andReturn(PHP_SESSION_ACTIVE);

                    $test = function () {

                        $this->middleware->process($this->request->get(), $this->handler->get());

                    };

                    $exception = new SessionAlreadyStartedException;

                    expect($test)->toThrow($exception);

                });

            });

            context('when the session is already closed', function () {

                it('should throw a SessionAlreadyClosedException', function () {

                    $statuses = [PHP_SESSION_NONE, PHP_SESSION_NONE, PHP_SESSION_NONE];

                    allow('session_status')->toBeCalled()->andReturn(...$statuses);

                    $test = function () {

                        $this->middleware->process($this->request->get(), $this->handler->get());

                    };

                    $exception = new SessionAlreadyClosedException;

                    expect($test)->toThrow($exception);

                });

            });

            context('when the request do not have a session cookie', function () {

                beforeEach(function () {

                    $this->request->getCookieParams->returns([]);

                });

                it('should not set the session id', function () {

                    $this->set = false;

                    allow('session_id')->toBeCalled()->with('incomingsessionid')->andRun(function () {

                        $this->set = true;

                    });

                    $middleware = new StartSessionMiddleware('cookie_name');

                    $middleware->process($this->request->get(), $this->handler->get());

                    expect($this->set)->toBeFalsy();

                });

                it('should attach the session id to the response', function () {

                    allow('session_id')->toBeCalled()->andReturn('newsessionid');

                    $middleware = new StartSessionMiddleware('cookie_name');

                    $test = $middleware->process($this->request->get(), $this->handler->get())
                        ->getHeaderLine('set-cookie');

                    expect($test)->toContain('cookie_name=newsessionid');

                });

            });

            context('when the request has a session cookie', function () {

                beforeEach(function () {

                    $this->request->getCookieParams->returns(['cookie_name' => 'incomingsessionid']);

                });

                it('should set the session id from the request', function () {

                    $this->set = false;

                    allow('session_id')->toBeCalled()->with('incomingsessionid')->andRun(function () {

                        $this->set = true;

                    });

                    $middleware = new StartSessionMiddleware('cookie_name');

                    $middleware->process($this->request->get(), $this->handler->get());

                    expect($this->set)->toBeTruthy();

                });

                it('should attach the session id to the response', function () {

                    allow('session_id')->toBeCalled()->andReturn('incomingsessionid');

                    $middleware = new StartSessionMiddleware('cookie_name');

                    $test = $middleware->process($this->request->get(), $this->handler->get())
                        ->getHeaderLine('set-cookie');

                    expect($test)->toContain('cookie_name=incomingsessionid');

                });

            });

            context('when no cookie name is given', function () {

                it('should return a response with a session cookie named ellipse_session', function () {

                    $test = $this->middleware->process($this->request->get(), $this->handler->get())
                        ->getHeaderLine('set-cookie');

                    $maxage = 3600;
                    $expires = gmdate('D, d M Y H:i:s T', time() + $maxage);

                    expect($test)->toContain('ellipse_session=' . session_id());

                });

            });

            context('when no cookie options array is given', function () {

                it('should return a response with a session cookie using the default options', function () {

                    $test = $this->middleware->process($this->request->get(), $this->handler->get())
                        ->getHeaderLine('set-cookie');

                    $maxage = 3600;
                    $expires = gmdate('D, d M Y H:i:s T', time() + $maxage);

                    expect($test)->toContain('Path=/default/path');
                    expect($test)->toContain('Domain=default.domain.com');
                    expect($test)->toContain('Expires=' . $expires);
                    expect($test)->toContain('Max-Age=' . $maxage);
                    expect($test)->not->toContain('Secure');
                    expect($test)->not->toContain('HttpOnly');

                });

            });

            context('when the given cookie options array contain a name key', function () {

                it('should set a cookie with this name value', function () {

                    $middleware = new StartSessionMiddleware('cookie_name');

                    $test = $middleware->process($this->request->get(), $this->handler->get())
                        ->getHeaderLine('set-cookie');

                    expect($test)->toContain('cookie_name=' . session_id());

                });

            });

            context('when the given cookie options array contain a path key', function () {

                it('should set a cookie with this path value', function () {

                    $middleware = new StartSessionMiddleware('cookie_name', ['path' => '/path']);

                    $test = $middleware->process($this->request->get(), $this->handler->get())
                        ->getHeaderLine('set-cookie');

                    expect($test)->toContain('Path=/path');

                });

            });

            context('when the given cookie options array contain a domain key', function () {

                it('should set a cookie with this domain value', function () {

                    $middleware = new StartSessionMiddleware('cookie_name', ['domain' => 'domain.com']);

                    $test = $middleware->process($this->request->get(), $this->handler->get())
                        ->getHeaderLine('set-cookie');

                    expect($test)->toContain('Domain=domain.com');

                });

            });

            context('when the given cookie options array contain a lifetime key', function () {

                context('when the lifetime is greater than 0', function () {

                    it('should set a cookie with expires and max-age values', function () {

                        $middleware = new StartSessionMiddleware('cookie_name', ['lifetime' => 7200]);

                        $test = $middleware->process($this->request->get(), $this->handler->get())
                            ->getHeaderLine('set-cookie');

                        $maxage = 7200;
                        $expires = gmdate('D, d M Y H:i:s T', time() + $maxage);

                        expect($test)->toContain('Expires=' . $expires);
                        expect($test)->toContain('Max-Age=' . $maxage);

                    });

                });

                context('when the lifetime is equal to 0', function () {

                    it('should set a cookie with no expires and no max-age values', function () {

                        $middleware = new StartSessionMiddleware('cookie_name', ['lifetime' => 0]);

                        $test = $middleware->process($this->request->get(), $this->handler->get())
                            ->getHeaderLine('set-cookie');

                        expect($test)->not->toContain('Expires');
                        expect($test)->not->toContain('Max-Age');

                    });

                });

                context('when the lifetime is lesser than 0', function () {

                    it('should set a cookie with no expires and no max-age values', function () {

                        $middleware = new StartSessionMiddleware('cookie_name', ['lifetime' => -1]);

                        $test = $middleware->process($this->request->get(), $this->handler->get())
                            ->getHeaderLine('set-cookie');

                        expect($test)->not->toContain('Expires');
                        expect($test)->not->toContain('Max-Age');

                    });

                });

            });

            context('when the given cookie options array contain a secure key', function () {

                context('when the secure value is true', function () {

                    it('should set a cookie with the secure value', function () {

                        $middleware = new StartSessionMiddleware('cookie_name', ['secure' => true]);

                        $test = $middleware->process($this->request->get(), $this->handler->get())
                            ->getHeaderLine('set-cookie');

                        expect($test)->toContain('Secure');

                    });

                });

                context('when the secure value is false', function () {

                    it('should set a cookie without the secure value', function () {

                        $middleware = new StartSessionMiddleware('cookie_name', ['secure' => false]);

                        $test = $middleware->process($this->request->get(), $this->handler->get())
                            ->getHeaderLine('set-cookie');

                        expect($test)->not->toContain('Secure');

                    });

                });

            });

            context('when the given cookie options array contain a httponly key', function () {

                context('when the httponly value is true', function () {

                    it('should set a cookie with the httponly value', function () {

                        $middleware = new StartSessionMiddleware('cookie_name', ['httponly' => true]);

                        $test = $middleware->process($this->request->get(), $this->handler->get())
                            ->getHeaderLine('set-cookie');

                        expect($test)->toContain('HttpOnly');

                    });

                });

                context('when the httponly value is false', function () {

                    it('should set a cookie without the httponly value', function () {

                        $middleware = new StartSessionMiddleware('cookie_name', ['httponly' => false]);

                        $test = $middleware->process($this->request->get(), $this->handler->get())
                            ->getHeaderLine('set-cookie');

                        expect($test)->not->toContain('HttpOnly');

                    });

                });

            });

            context('when the cookie options array keys are uppercased', function () {

                it('should return a cookie with the given values anyway', function () {

                    $middleware = new StartSessionMiddleware('cookie_name', [
                        'PATH' => '/path',
                        'DOMAIN' => 'domain.com',
                        'LIFETIME' => 7200,
                        'SECURE' => true,
                        'HTTPONLY' => true,
                    ]);

                    $test = $middleware->process($this->request->get(), $this->handler->get())
                        ->getHeaderLine('set-cookie');

                    $maxage = 7200;
                    $expires = gmdate('D, d M Y H:i:s T', time() + $maxage);

                    expect($test)->toContain('cookie_name=' . session_id());
                    expect($test)->toContain('Path=/path');
                    expect($test)->toContain('Domain=domain.com');
                    expect($test)->toContain('Expires=' . $expires);
                    expect($test)->toContain('Max-Age=' . $maxage);
                    expect($test)->toContain('Secure');
                    expect($test)->toContain('HttpOnly');

                });

            });

        });

        context('when session_start() returns false', function () {

            it('should throw SessionStartException', function () {

                $options = StartSessionMiddleware::SESSION_OPTIONS;

                allow('session_start')->toBeCalled()->with($options)->andReturn(false);

                $test = function () {

                    $this->middleware->process($this->request->get(), $this->handler->get());

                };

                $exception = new SessionStartException;

                expect($test)->toThrow($exception);

            });

        });

    });

});
