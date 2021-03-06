<?php

/*
 * This file is part of the Phony package.
 *
 * Copyright © 2016 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Phony\Assertion\Exception;

use PHPUnit_Framework_TestCase;

class AssertionExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $message = 'message';
        $exception = new AssertionException($message);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function tracePhonyCallData()
    {
        return array(
            'Method' => array(
                array(
                    array(
                        'file' => '/path/to/file/a',
                        'line' => 111,
                        'function' => 'methodA',
                        'class' =>  'Eloquent\Phony\ClassA',
                    ),
                    array(
                        'file' => '/path/to/file/b',
                        'line' => 222,
                        'function' => 'methodB',
                        'class' =>  'Eloquent\Phony\ClassB',
                    ),
                    array(
                        'file' => '/path/to/file/c',
                        'line' => 333,
                        'function' => 'methodC',
                        'class' => 'ClassC',
                    ),
                ),
                array(
                    'file' => '/path/to/file/b',
                    'line' => 222,
                    'function' => 'methodB',
                    'class' =>  'Eloquent\Phony\ClassB',
                ),
            ),

            'Function' => array(
                array(
                    array(
                        'file' => '/path/to/file/a',
                        'line' => 111,
                        'function' => 'methodA',
                        'class' =>  'Eloquent\Phony\ClassA',
                    ),
                    array(
                        'file' => '/path/to/file/b',
                        'line' => 222,
                        'function' => 'Eloquent\Phony\functionB',
                    ),
                    array(
                        'file' => '/path/to/file/c',
                        'line' => 333,
                        'function' => 'functionC',
                    ),
                ),
                array(
                    'file' => '/path/to/file/b',
                    'line' => 222,
                    'function' => 'Eloquent\Phony\functionB',
                ),
            ),

            'No external calls' => array(
                array(
                    array(
                        'file' => '/path/to/file/a',
                        'line' => 111,
                        'function' => 'methodA',
                        'class' =>  'Eloquent\Phony\ClassA',
                    ),
                    array(
                        'file' => '/path/to/file/b',
                        'line' => 222,
                        'function' => 'Eloquent\Phony\functionB',
                    ),
                ),
                array(
                    'file' => '/path/to/file/b',
                    'line' => 222,
                    'function' => 'Eloquent\Phony\functionB',
                ),
            ),

            'Direct construction from outside namespace' => array(
                array(
                    array(
                        'file' => '/path/to/file/a',
                        'line' => 111,
                        'function' => 'functionA',
                    ),
                ),
                null,
            ),

            'Empty Trace' => array(
                array(),
                null,
            ),
        );
    }

    /**
     * @dataProvider tracePhonyCallData
     */
    public function testTracePhonyCall($trace, $expected)
    {
        $this->assertSame($expected, AssertionException::tracePhonyCall($trace));
    }
}
