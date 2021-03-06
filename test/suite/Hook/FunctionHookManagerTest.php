<?php

/*
 * This file is part of the Phony package.
 *
 * Copyright © 2016 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Phony\Hook;

use Eloquent\Phony\Reflection\FunctionSignatureInspector;
use Eloquent\Phony\Test\TestFunctionHookGenerator;
use PHPUnit_Framework_TestCase;
use ReflectionClass;

class FunctionHookManagerTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->functionSignatureInspector = FunctionSignatureInspector::instance();
        $this->hookGenerator = FunctionHookGenerator::instance();
        $this->subject = new FunctionHookManager($this->functionSignatureInspector, $this->hookGenerator);

        $this->namespace = 'Eloquent\Phony\Test\FunctionHookManager';
        $this->name = 'phony_' . md5(mt_rand());
        $this->fullName = $this->namespace . '\\' . $this->name;
    }

    public function testDefineFunction()
    {
        $callbackA = function () {
            return implode(', ', func_get_args());
        };
        $callbackB = function () {};

        $this->assertFalse(function_exists($this->fullName));

        $this->subject->defineFunction($this->name, $this->namespace, $callbackA);

        $this->assertTrue(function_exists($this->fullName));
        $this->assertSame('a, b, c', call_user_func($this->fullName, 'a', 'b', 'c'));
        $this->assertSame($callbackA, $this->subject->defineFunction($this->name, $this->namespace, $callbackB));
        $this->assertNull(call_user_func($this->fullName, 'a', 'b', 'c'));
    }

    public function testDefineFunctionWithReferenceParameters()
    {
        $callback = function (&$a, &$b) {
            $a = 'a';
            $b = 'b';
        };

        $this->subject->defineFunction($this->name, $this->namespace, $callback);

        $a = null;
        $b = null;
        call_user_func_array($this->fullName, array(&$a, &$b));

        $this->assertSame('a', $a);
        $this->assertSame('b', $b);
    }

    public function testDefineFunctionFailureSignatureMismatch()
    {
        $this->subject->defineFunction($this->name, $this->namespace, function ($a = null) {});

        $this->setExpectedException('Eloquent\Phony\Hook\Exception\FunctionSignatureMismatchException');
        $this->subject->defineFunction($this->name, $this->namespace, function () {});
    }

    public function testDefineFunctionFailureExistantFunction()
    {
        if (!function_exists($this->namespace . '\\existant')) {
            eval("namespace $this->namespace;\nfunction existant () {}");
        }

        $this->setExpectedException('Eloquent\Phony\Hook\Exception\FunctionExistsException');
        $this->subject->defineFunction('existant', $this->namespace, function () {});
    }

    public function testDefineFunctionFailureSyntax()
    {
        $this->hookGenerator = new TestFunctionHookGenerator('{');
        $this->subject = new FunctionHookManager($this->functionSignatureInspector, $this->hookGenerator);

        $this->setExpectedException('Eloquent\Phony\Hook\Exception\FunctionHookGenerationFailedException');
        $this->subject->defineFunction($this->name, $this->namespace, function () {});
    }

    public function testRestoreGlobalFunctions()
    {
        $this->subject->defineFunction(
            'sprintf',
            $this->namespace,
            function ($pattern) {
                return 'x';
            }
        );
        $this->subject->defineFunction(
            'vsprintf',
            $this->namespace,
            function ($pattern) {
                return 'y';
            }
        );

        $this->assertSame('x', call_user_func($this->namespace . '\\sprintf', '%s', 'a'));
        $this->assertSame('y', call_user_func($this->namespace . '\\vsprintf', '%s', array('b')));

        $this->subject->restoreGlobalFunctions();

        $this->assertSame('a', call_user_func($this->namespace . '\\sprintf', '%s', 'a'));
        $this->assertSame('b', call_user_func($this->namespace . '\\vsprintf', '%s', array('b')));
    }

    public function testInstance()
    {
        $class = get_class($this->subject);
        $reflector = new ReflectionClass($class);
        $property = $reflector->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
        $instance = $class::instance();

        $this->assertInstanceOf($class, $instance);
        $this->assertSame($instance, $class::instance());
    }
}
