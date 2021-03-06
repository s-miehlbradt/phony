<?php

/*
 * This file is part of the Phony package.
 *
 * Copyright © 2016 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Phony\Mock\Exception;

use PHPUnit_Framework_TestCase;

class AnonymousClassExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $exception = new AnonymousClassException();

        $this->assertSame('Anonymous classes cannot be mocked.', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
