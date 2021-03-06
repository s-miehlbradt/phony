<?php

use Eloquent\Phony\Test\Phony;

// setup
$stub = Phony::stub()
    ->setLabel('label')
    ->setUseIterableSpies(true)
    ->returnsArgument();
$stub(array('aardvark', 'bonobo', 'chameleon'));
foreach ($stub(array('aardvark', 'bonobo', 'chameleon')) as $value) {
    if ('bonobo' === $value) {
        break;
    }
}

// verification
$stub->lastCall()->completed();
