<?php

/*
 * This file is part of the Phony package.
 *
 * Copyright © 2016 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Phony\Test;

abstract class GeneratorFactory
{
    public static function createEmpty()
    {
        return call_user_func(function () { return; yield null; });
    }
}
