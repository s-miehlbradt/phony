<?php

use Eloquent\Phony\Test\Phony;

// setup
$stub = Phony::stub(
    function ($animal) {
        throw new RuntimeException(strtoupper($animal));
    }
)->setLabel('label')->forwards();
try {
    $stub('aardvark');
} catch (RuntimeException $e) {
}
try {
    $stub('bonobo');
} catch (RuntimeException $e) {
}

// verification
$stub->always()->threw(new RuntimeException('BONOBO'));
