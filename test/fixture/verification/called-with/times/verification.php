<?php

use Eloquent\Phony\Test\Phony;

// setup
$spy = Phony::spy()->setLabel('label');
$spy('aardvark', array('bonobo', 'capybara', 'dugong'));
$spy('armadillo', array('bonobo', 'chameleon', 'dormouse'));
$spy('aardvark', array('bonobo', 'capybara', 'dugong'));

// verification
$spy->times(3)->calledWith('aardvark', array('bonobo', 'capybara', 'dugong'));
