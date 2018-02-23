<?php

use Ellipse\Session\Exceptions\SessionAlreadyClosedException;
use Ellipse\Session\Exceptions\SessionStartExceptionInterface;

describe('SessionAlreadyClosedException', function () {

    beforeEach(function () {

        $this->exception = new SessionAlreadyClosedException;

    });

    it('should implement SessionStartExceptionInterface', function () {

        expect($this->exception)->toBeAnInstanceOf(SessionStartExceptionInterface::class);

    });

    it('should extend RuntimeException', function () {

        expect($this->exception)->toBeAnInstanceOf(RuntimeException::class);

    });

});
