<?php

use Ellipse\Session\Exceptions\SessionStartException;
use Ellipse\Session\Exceptions\SessionStartExceptionInterface;

describe('SessionStartException', function () {

    it('should implement SessionStartExceptionInterface', function () {

        $test = new SessionStartException;

        expect($test)->toBeAnInstanceOf(SessionStartExceptionInterface::class);

    });

});
