<?php

use Ellipse\Session\Exceptions\SessionsDisabledException;

describe('SessionsDisabledException', function () {

    beforeEach(function () {

        $this->exception = new SessionsDisabledException;

    });

    it('should extend RuntimeException', function () {

        expect($this->exception)->toBeAnInstanceOf(RuntimeException::class);

    });

});
