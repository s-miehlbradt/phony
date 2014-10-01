<?php

/*
 * This file is part of the Phony package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Phony\Assertion\Renderer;

use Eloquent\Phony\Call\Call;
use Eloquent\Phony\Matcher\EqualToMatcher;
use Exception;
use PHPUnit_Framework_TestCase;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use RuntimeException;
use SebastianBergmann\Exporter\Exporter;

class AssertionRendererTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->exporter = new Exporter();
        $this->subject = new AssertionRenderer($this->exporter);

        $this->callA = new Call(
            new ReflectionMethod(__METHOD__),
            array('argumentA', 'argumentB'),
            'returnValue',
            0,
            .1,
            .2,
            new RuntimeException('message'),
            (object) array()
        );
        $this->callB = new Call(new ReflectionMethod(__METHOD__), array(), null, 1, .3, .4);
    }

    public function testConstructor()
    {
        $this->assertSame($this->exporter, $this->subject->exporter());
    }

    public function testConstructorDefaults()
    {
        $this->subject = new AssertionRenderer();

        $this->assertEquals($this->exporter, $this->subject->exporter());
    }

    public function testRenderValue()
    {
        $this->assertSame("'value'", $this->subject->renderValue('value'));
        $this->assertSame("111", $this->subject->renderValue(111));
        $this->assertSame("'line\nline'", $this->subject->renderValue("line\nline"));
        $this->assertSame(
            "'12345678901234567890123456789012345678901234567890'",
            $this->subject->renderValue('12345678901234567890123456789012345678901234567890')
        );
    }

    public function testRenderMatchers()
    {
        $matcherA = new EqualToMatcher('argumentA');
        $matcherB = new EqualToMatcher(111);

        $this->assertSame("<none>", $this->subject->renderMatchers(array()));
        $this->assertSame("<'argumentA'>", $this->subject->renderMatchers(array($matcherA)));
        $this->assertSame("<'argumentA'>, <111>", $this->subject->renderMatchers(array($matcherA, $matcherB)));
    }

    public function testRenderCalls()
    {
        $expected = <<<'EOD'
    - Eloquent\Phony\Assertion\Renderer\AssertionRendererTest->setUp('argumentA', 'argumentB')
    - Eloquent\Phony\Assertion\Renderer\AssertionRendererTest->setUp()
EOD;

        $this->assertSame('', $this->subject->renderCalls(array()));
        $this->assertSame($expected, $this->subject->renderCalls(array($this->callA, $this->callB)));
    }

    public function testRenderCallsArguments()
    {
        $expected = <<<'EOD'
    - 'argumentA', 'argumentB'
    - <none>
EOD;

        $this->assertSame('', $this->subject->renderCallsArguments(array()));
        $this->assertSame($expected, $this->subject->renderCallsArguments(array($this->callA, $this->callB)));
    }

    public function testRenderReturnValues()
    {
        $expected = <<<'EOD'
    - 'returnValue'
    - null
EOD;

        $this->assertSame('', $this->subject->renderReturnValues(array()));
        $this->assertSame($expected, $this->subject->renderReturnValues(array($this->callA, $this->callB)));
    }

    public function testRenderThrownExceptions()
    {
        $expected = <<<'EOD'
    - RuntimeException('message')
    - <none>
EOD;

        $this->assertSame('', $this->subject->renderThrownExceptions(array()));
        $this->assertSame($expected, $this->subject->renderThrownExceptions(array($this->callA, $this->callB)));
    }

    public function testRenderThisValues()
    {
        $expected = <<<'EOD'
    - stdClass Object ()
    - null
EOD;

        $this->assertSame('', $this->subject->renderThisValues(array()));
        $this->assertSame($expected, $this->subject->renderThisValues(array($this->callA, $this->callB)));
    }

    public function renderCallData()
    {
        return array(
            'Method' => array(
                new Call(new ReflectionMethod(__METHOD__), array(), null, 0, .1, .2),
                "Eloquent\Phony\Assertion\Renderer\AssertionRendererTest->renderCallData()",
            ),
            'Static method' => array(
                new Call(new ReflectionMethod('ReflectionMethod::export'), array(), null, 0, .1, .2),
                "ReflectionMethod::export()",
            ),
            'Function' => array(
                new Call(new ReflectionFunction('function_exists'), array(), null, 0, .1, .2),
                "function_exists()",
            ),
            'Closure' => array(
                new Call(new ReflectionFunction(function () {}), array(), null, 0, .1, .2),
                "Eloquent\Phony\Assertion\Renderer\{closure}()",
            ),
            'With arguments' => array(
                new Call(new ReflectionFunction('function_exists'), array('argument', 111), null, 0, .1, .2),
                "function_exists('argument', 111)",
            ),
        );
    }

    /**
     * @dataProvider renderCallData
     */
    public function testRenderCall($call, $expected)
    {
        $this->assertSame($expected, $this->subject->renderCall($call));
    }

    public function testRenderException()
    {
        $this->assertSame("Exception()", $this->subject->renderException(new Exception()));
        $this->assertSame("RuntimeException()", $this->subject->renderException(new RuntimeException()));
        $this->assertSame("Exception('message')", $this->subject->renderException(new Exception('message')));
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