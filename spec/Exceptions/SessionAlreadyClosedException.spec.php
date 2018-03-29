<?php

use Ellipse\Session\Exceptions\SessionAlreadyClosedException;
use Ellipse\Session\Exceptions\SessionStartExceptionInterface;

describe('SessionAlreadyClosedException', function () {

    it('should implement SessionStartExceptionInterface', function () {

        $test = new SessionAlreadyClosedException;

        expect($test)->toBeAnInstanceOf(SessionStartExceptionInterface::class);

    });

});
