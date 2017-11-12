<?php

use function Eloquent\Phony\Kahlan\mock;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;

use Zend\Diactoros\Response\TextResponse;

use Ellipse\Session\StartSessionMiddleware;
use Ellipse\Session\Exceptions\SessionsDisabledException;
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
            $nocookie = ['use_cookies' => false, 'use_only_cookies' => true];

            allow('session_status')->toBeCalled()->andReturn(...$statuses);
            allow('session_start')->toBeCalled()->with($nocookie)->andReturn(true);
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

            it('should throw a SessionsDisabledException', function () {

                allow('session_status')->toBeCalled()->andReturn(PHP_SESSION_DISABLED);

                $test = function () {

                    $this->middleware->process($this->request->get(), $this->handler->get());

                };

                $exception = new SessionsDisabledException;

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

            it('should create a new session id', function () {

                $this->request->getCookieParams->returns([]);

                $middleware = new StartSessionMiddleware(['name' => 'cookie_name']);

                $test = $middleware->process($this->request->get(), $this->handler->get())
                    ->getHeaderLine('set-cookie');

                expect($test)->toContain('cookie_name=' . session_id());

            });

        });

        context('when the request has a session cookie', function () {

            it('should use the session id from the request', function () {

                $this->request->getCookieParams->returns(['cookie_name' => 'incomingsessionid']);

                $middleware = new StartSessionMiddleware(['name' => 'cookie_name']);

                $test = $middleware->process($this->request->get(), $this->handler->get())
                    ->getHeaderLine('set-cookie');

                expect($test)->toContain('cookie_name=incomingsessionid');

            });

        });

        context('when no cookie options array is given', function () {

            it('should return a response with a session cookie using the default options', function () {

                $test = $this->middleware->process($this->request->get(), $this->handler->get())
                    ->getHeaderLine('set-cookie');

                $timestamp = time() + 3600;
                $date = gmdate('D, d M Y H:i:s T', $timestamp);

                expect($test)->toContain('default_cookie_name=' . session_id());
                expect($test)->toContain('Path=/default/path');
                expect($test)->toContain('Domain=default.domain.com');
                expect($test)->toContain('Expires=' . $date);
                expect($test)->toContain('Max-Age=' . $timestamp);
                expect($test)->not->toContain('Secure');
                expect($test)->not->toContain('HttpOnly');

            });

        });

        context('when the given cookie options array contain a name key', function () {

            it('should set a cookie with this name value', function () {

                $middleware = new StartSessionMiddleware(['name' => 'cookie_name']);

                $test = $middleware->process($this->request->get(), $this->handler->get())
                    ->getHeaderLine('set-cookie');

                expect($test)->toContain('cookie_name=' . session_id());

            });

        });

        context('when the given cookie options array contain a path key', function () {

            it('should set a cookie with this path value', function () {

                $middleware = new StartSessionMiddleware(['path' => '/path']);

                $test = $middleware->process($this->request->get(), $this->handler->get())
                    ->getHeaderLine('set-cookie');

                expect($test)->toContain('Path=/path');

            });

        });

        context('when the given cookie options array contain a domain key', function () {

            it('should set a cookie with this domain value', function () {

                $middleware = new StartSessionMiddleware(['domain' => 'domain.com']);

                $test = $middleware->process($this->request->get(), $this->handler->get())
                    ->getHeaderLine('set-cookie');

                expect($test)->toContain('Domain=domain.com');

            });

        });

        context('when the given cookie options array contain a lifetime key', function () {

            it('should set a cookie with expires and max-age values', function () {

                $middleware = new StartSessionMiddleware(['lifetime' => 7200]);

                $test = $middleware->process($this->request->get(), $this->handler->get())
                    ->getHeaderLine('set-cookie');

                $timestamp = time() + 7200;
                $date = gmdate('D, d M Y H:i:s T', $timestamp);

                expect($test)->toContain('Expires=' . $date);
                expect($test)->toContain('Max-Age=' . $timestamp);

            });

        });

        context('when the given cookie options array contain a secure key', function () {

            context('when the secure value is true', function () {

                it('should set a cookie with the secure value', function () {

                    $middleware = new StartSessionMiddleware(['secure' => true]);

                    $test = $middleware->process($this->request->get(), $this->handler->get())
                        ->getHeaderLine('set-cookie');

                    expect($test)->toContain('Secure');

                });

            });

            context('when the secure value is false', function () {

                it('should set a cookie without the secure value', function () {

                    $middleware = new StartSessionMiddleware(['secure' => false]);

                    $test = $middleware->process($this->request->get(), $this->handler->get())
                        ->getHeaderLine('set-cookie');

                    expect($test)->not->toContain('Secure');

                });

            });

        });

        context('when the given cookie options array contain a httponly key', function () {

            context('when the httponly value is true', function () {

                it('should set a cookie with the httponly value', function () {

                    $middleware = new StartSessionMiddleware(['httponly' => true]);

                    $test = $middleware->process($this->request->get(), $this->handler->get())
                        ->getHeaderLine('set-cookie');

                    expect($test)->toContain('HttpOnly');

                });

            });

            context('when the httponly value is false', function () {

                it('should set a cookie without the httponly value', function () {

                    $middleware = new StartSessionMiddleware(['httponly' => false]);

                    $test = $middleware->process($this->request->get(), $this->handler->get())
                        ->getHeaderLine('set-cookie');

                    expect($test)->not->toContain('HttpOnly');

                });

            });

        });

        context('when the cookie options array keys are uppercased', function () {

            it('should return a cookie with the given values anyway', function () {

                $middleware = new StartSessionMiddleware([
                    'NAME' => 'cookie_name',
                    'PATH' => '/path',
                    'DOMAIN' => 'domain.com',
                    'LIFETIME' => 7200,
                    'SECURE' => true,
                    'HTTPONLY' => true,
                ]);

                $test = $middleware->process($this->request->get(), $this->handler->get())
                    ->getHeaderLine('set-cookie');

                $timestamp = time() + 7200;
                $date = gmdate('D, d M Y H:i:s T', $timestamp);

                expect($test)->toContain('cookie_name=' . session_id());
                expect($test)->toContain('Path=/path');
                expect($test)->toContain('Domain=domain.com');
                expect($test)->toContain('Expires=' . $date);
                expect($test)->toContain('Max-Age=' . $timestamp);
                expect($test)->toContain('Secure');
                expect($test)->toContain('HttpOnly');

            });

        });

    });

});
