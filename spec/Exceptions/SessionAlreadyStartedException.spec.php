<?php

use Ellipse\Session\Exceptions\SessionAlreadyStartedException;
use Ellipse\Session\Exceptions\SessionStartExceptionInterface;

describe('SessionAlreadyStartedException', function () {

    it('should implement SessionStartExceptionInterface', function () {

        $test = new SessionAlreadyStartedException;

        expect($test)->toBeAnInstanceOf(SessionStartExceptionInterface::class);

    });

});
