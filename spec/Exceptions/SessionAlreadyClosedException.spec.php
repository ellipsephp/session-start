<?php

use Ellipse\Session\Exceptions\SessionAlreadyClosedException;

describe('SessionAlreadyClosedException', function () {

    beforeEach(function () {

        $this->exception = new SessionAlreadyClosedException;

    });

    it('should extend RuntimeException', function () {

        expect($this->exception)->toBeAnInstanceOf(RuntimeException::class);

    });

});
