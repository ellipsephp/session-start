<?php

use Ellipse\Session\Exceptions\SessionsDisabledException;
use Ellipse\Session\Exceptions\SessionStartExceptionInterface;

describe('SessionsDisabledException', function () {

    beforeEach(function () {

        $this->exception = new SessionsDisabledException;

    });

    it('should implement SessionStartExceptionInterface', function () {

        expect($this->exception)->toBeAnInstanceOf(SessionStartExceptionInterface::class);

    });

    it('should extend RuntimeException', function () {

        expect($this->exception)->toBeAnInstanceOf(RuntimeException::class);

    });

});
