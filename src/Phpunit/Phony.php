<?php

/*
 * This file is part of the Phony package.
 *
 * Copyright © 2016 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Phony\Phpunit;

use Eloquent\Phony\Facade\AbstractFacade;
use Eloquent\Phony\Facade\FacadeDriver;
use Eloquent\Phony\Integration\Phpunit\PhpunitFacadeDriver;

/**
 * A facade for Phony usage under PHPUnit.
 */
class Phony extends AbstractFacade
{
    /**
     * Get the facade driver.
     *
     * @return FacadeDriver The facade driver.
     */
    protected static function driver()
    {
        return PhpunitFacadeDriver::instance();
    }
}
