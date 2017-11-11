<?php

use Ellipse\Session\Exceptions\SessionAlreadyStartedException;

describe('SessionAlreadyStartedException', function () {

    beforeEach(function () {

        $this->exception = new SessionAlreadyStartedException;

    });

    it('should extend RuntimeException', function () {

        expect($this->exception)->toBeAnInstanceOf(RuntimeException::class);

    });

});
