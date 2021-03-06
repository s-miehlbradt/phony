<?php

use Eloquent\Phony\Test\Phony;

// setup
$stub = Phony::stub()->setLabel('label')->setUseIterableSpies(true);
$stub->with('aardvark')->returns(array('AARDVARK'));
$stub->with('bonobo')->returns(array('BONOBO', 'BADGER'));
$stub('aardvark');
iterator_to_array($stub('bonobo'));

// verification
$stub->lastCall()->iterated()->times(3)->produced();
