<?php

use Eloquent\Phony\Test\Phony;

// setup
$stub = Phony::stub(
    function ($animal) {
        return strtoupper($animal);
    }
)->setLabel('label')->forwards();
$stub('aardvark');
$stub('bonobo');

// verification
$stub->between(3, 4)->returned();
