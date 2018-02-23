<?php

use Ellipse\Session\Exceptions\SessionAlreadyStartedException;
use Ellipse\Session\Exceptions\SessionStartExceptionInterface;

describe('SessionAlreadyStartedException', function () {

    beforeEach(function () {

        $this->exception = new SessionAlreadyStartedException;

    });

    it('should implement SessionStartExceptionInterface', function () {

        expect($this->exception)->toBeAnInstanceOf(SessionStartExceptionInterface::class);

    });

    it('should extend RuntimeException', function () {

        expect($this->exception)->toBeAnInstanceOf(RuntimeException::class);

    });

});
