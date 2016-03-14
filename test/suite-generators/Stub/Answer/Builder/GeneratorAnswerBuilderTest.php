<?php

/*
 * This file is part of the Phony package.
 *
 * Copyright © 2016 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Phony\Stub\Answer\Builder;

use ArrayIterator;
use Eloquent\Phony\Call\Argument\Arguments;
use Eloquent\Phony\Feature\FeatureDetector;
use Eloquent\Phony\Invocation\InvocableInspector;
use Eloquent\Phony\Invocation\Invoker;
use Eloquent\Phony\Phpunit\Phony;
use Eloquent\Phony\Stub\Stub;
use Exception;
use PHPUnit_Framework_TestCase;

class GeneratorAnswerBuilderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->self = (object) array();
        $this->stub = new Stub(null, $this->self);
        $this->featureDetector = FeatureDetector::instance();
        $this->isGeneratorReturnSupported = $this->featureDetector->isSupported('generator.return');
        $this->invocableInspector = new InvocableInspector();
        $this->invoker = new Invoker();
        $this->subject = new GeneratorAnswerBuilder(
            $this->stub,
            $this->isGeneratorReturnSupported,
            $this->invocableInspector,
            $this->invoker
        );

        $this->answer = $this->subject->answer();

        $this->callsA = array();
        $callsA = &$this->callsA;
        $this->callCountA = 0;
        $callCountA = &$this->callCountA;
        $this->callbackA = function () use (&$callsA, &$callCountA) {
            $arguments = func_get_args();
            $callsA[] = $arguments;
            ++$callCountA;

            array_unshift($arguments, 'A');

            return $arguments;
        };

        $this->callsB = array();
        $callsB = &$this->callsB;
        $this->callCountB = 0;
        $callCountB = &$this->callCountB;
        $this->callbackB = function () use (&$callsB, &$callCountB) {
            $arguments = func_get_args();
            $callsB[] = $arguments;
            ++$callCountB;

            array_unshift($arguments, 'B');

            return $arguments;
        };

        $this->callsC = array();
        $callsC = &$this->callsC;
        $this->callCountC = 0;
        $callCountC = &$this->callCountC;
        $this->callbackC = function () use (&$callsC, &$callCountC) {
            $arguments = func_get_args();
            $callsC[] = $arguments;
            ++$callCountC;

            array_unshift($arguments, 'C');

            return $arguments;
        };

        $this->referenceCallback = function (&$a, &$b = null, &$c = null, &$d = null) {
            $a = 'a';
            $b = 'b';
            $c = 'c';
            $d = 'd';
        };

        $this->arguments = Arguments::create();
    }

    public function testConstructor()
    {
        $this->assertSame($this->stub, $this->subject->stub());
        $this->assertSame($this->invocableInspector, $this->subject->invocableInspector());
        $this->assertSame($this->invoker, $this->subject->invoker());
    }

    public function testCalls()
    {
        $this->assertSame(
            $this->subject,
            $this->subject->calls($this->callbackA, $this->callbackB)->calls($this->callbackC)
        );

        iterator_to_array(call_user_func($this->answer, $this->self, Arguments::create('a', 'b')));
        $expected = array(array('a', 'b'));

        $this->assertSame($expected, $this->callsA);
        $this->assertSame($expected, $this->callsB);
        $this->assertSame($expected, $this->callsC);
    }

    public function testCallsWithReferenceParameters()
    {
        $a = null;
        $b = null;
        $this->subject->calls($this->referenceCallback);
        iterator_to_array(call_user_func($this->answer, $this->self, new Arguments(array(&$a, &$b))));

        $this->assertSame('a', $a);
        $this->assertSame('b', $b);
    }

    public function testCallsWith()
    {
        $this->assertSame(
            $this->subject,
            $this->subject
                ->callsWith($this->callbackA, array('A', 'B'), true, true, true)
                ->callsWith($this->callbackA, array('C', 'D'), true, true, true)
                ->callsWith($this->callbackB, array('E', 'F'), true, true, true)
        );

        $arguments = Arguments::create('a', 'b');
        iterator_to_array(call_user_func($this->answer, $this->self, $arguments));

        $this->assertEquals(
            array(
                array($this->self, 'A', 'B', $arguments, 'a', 'b'),
                array($this->self, 'C', 'D', $arguments, 'a', 'b'),
            ),
            $this->callsA
        );
        $this->assertEquals(
            array(
                array($this->self, 'E', 'F', $arguments, 'a', 'b'),
            ),
            $this->callsB
        );
    }

    public function testCallsWithDefaults()
    {
        $this->assertSame($this->subject, $this->subject->callsWith($this->callbackA));

        $arguments = Arguments::create('a', 'b');
        iterator_to_array(call_user_func($this->answer, $this->self, $arguments));

        $this->assertEquals(array(array('a', 'b')), $this->callsA);
    }

    public function testCallsWithSelfParameterAutoDetection()
    {
        $actual = null;
        $this->subject->callsWith(
                function ($phonySelf) use (&$actual) {
                    $actual = func_get_args();
                }
            )
            ->returns();
        $arguments = Arguments::create('a', 'b');
        iterator_to_array(call_user_func($this->answer, $this->self, $arguments));

        $this->assertSame(array($this->self, 'a', 'b'), $actual);
    }

    public function testCallsWithWithReferenceParameters()
    {
        $a = null;
        $b = null;
        $c = null;
        $d = null;
        $this->subject->callsWith($this->referenceCallback, array(&$a, &$b));
        $arguments = new Arguments(array(&$c, &$d));
        iterator_to_array(call_user_func($this->answer, $this->self, $arguments));

        $this->assertSame('a', $a);
        $this->assertSame('b', $b);
        $this->assertSame('c', $c);
        $this->assertSame('d', $d);
    }

    public function testCallsArgument()
    {
        $this->assertSame(
            $this->subject,
            $this->subject
                ->callsArgument(0, 1, 2, 111, -111)
                ->callsArgument()
        );

        $arguments = Arguments::create($this->callbackA, $this->callbackB, 'c');
        iterator_to_array(call_user_func($this->answer, $this->self, $arguments));

        $this->assertEquals(
            array(
                array($this->callbackA, $this->callbackB, 'c'),
                array($this->callbackA, $this->callbackB, 'c'),
            ),
            $this->callsA
        );
        $this->assertEquals(
            array(
                array($this->callbackA, $this->callbackB, 'c'),
            ),
            $this->callsB
        );
    }

    public function testCallsArgumentWith()
    {
        $this->assertSame(
            $this->subject,
            $this->subject
                ->callsArgumentWith()
                ->callsArgumentWith(0, array('A', 'B'), true, true, true)
                ->callsArgumentWith(0, array('C', 'D'), true, true, true)
                ->callsArgumentWith(1, array('E', 'F'), true, true, true)
                ->callsArgumentWith(2, array('X', 'Y'), true, true, true)
                ->callsArgumentWith(111, array('X', 'Y'), true, true, true)
                ->callsArgumentWith(-111, array('X', 'Y'), true, true, true)
        );

        $arguments = Arguments::create($this->callbackA, $this->callbackB, 'c');
        iterator_to_array(call_user_func($this->answer, $this->self, $arguments));

        $this->assertEquals(
            array(
                array($this->callbackA, $this->callbackB, 'c'),
                array($this->self, 'A', 'B', $arguments, $this->callbackA, $this->callbackB, 'c'),
                array($this->self, 'C', 'D', $arguments, $this->callbackA, $this->callbackB, 'c'),
            ),
            $this->callsA
        );
        $this->assertEquals(
            array(
                array($this->self, 'E', 'F', $arguments, $this->callbackA, $this->callbackB, 'c'),
            ),
            $this->callsB
        );
    }

    public function testCallsArgumentWithWithReferenceParameters()
    {
        $a = null;
        $b = null;
        $c = null;
        $d = null;
        $this->subject->callsArgumentWith(2, array(&$a, &$b), false, false, true);
        $arguments = new Arguments(array(&$c, &$d, $this->referenceCallback));
        iterator_to_array(call_user_func($this->answer, $this->self, $arguments));

        $this->assertSame('a', $a);
        $this->assertSame('b', $b);
        $this->assertSame('c', $c);
        $this->assertSame('d', $d);
    }

    public function testSetsArgument()
    {
        $this->assertSame(
            $this->subject,
            $this->subject
                ->setsArgument('a')
                ->setsArgument(1, 'b')
                ->setsArgument(-1, 'c')
                ->setsArgument(111, 'd')
                ->setsArgument(-111, 'e')
        );

        $a = null;
        $b = null;
        $c = null;
        $arguments = new Arguments(array(&$a, &$b, &$c));
        iterator_to_array(call_user_func($this->answer, $this->self, $arguments));

        $this->assertSame('a', $a);
        $this->assertSame('b', $b);
        $this->assertSame('c', $c);
    }

    public function testSetsArgumentWithInstanceHandles()
    {
        $adaptable = Phony::mock();
        $unadaptable = Phony::mock()->setIsAdaptable(false);
        $this->subject->setsArgument(0, $adaptable)->setsArgument(1, $unadaptable);

        $a = null;
        $b = null;
        $arguments = new Arguments(array(&$a, &$b));
        iterator_to_array(call_user_func($this->answer, $this->self, $arguments));

        $this->assertSame($adaptable->mock(), $a);
        $this->assertSame($unadaptable, $b);
    }

    public function testYields()
    {
        $this->assertSame($this->subject, $this->subject->yields('a', 'b')->yields('c')->yields());
        $this->assertSame(
            array('a' => 'b', 0 => 'c', 1 => null),
            iterator_to_array(call_user_func($this->answer, $this->self, $this->arguments))
        );
    }

    public function testYieldsWithSubRequests()
    {
        $arguments = Arguments::create('b', 'c');

        $this->assertSame($this->subject, $this->subject->calls($this->callbackA, $this->callbackB)->yields('a'));
        $this->assertSame(array('a'), iterator_to_array(call_user_func($this->answer, $this->self, $arguments)));
        $this->assertSame(array(array('b', 'c')), $this->callsA);
        $this->assertSame(array(array('b', 'c')), $this->callsB);
    }

    public function testYieldsFrom()
    {
        $values = array('a' => 'b', 'c' => 'd');

        $this->assertSame($this->subject, $this->subject->yieldsFrom($values));
        $this->assertSame($values, iterator_to_array(call_user_func($this->answer, $this->self, $this->arguments)));
    }

    public function testYieldsFromWithIterator()
    {
        $values = array('a' => 'b', 'c' => 'd');

        $this->assertSame($this->subject, $this->subject->yieldsFrom(new ArrayIterator($values)));
        $this->assertSame($values, iterator_to_array(call_user_func($this->answer, $this->self, $this->arguments)));
    }

    public function testYieldsFromWithSubRequests()
    {
        $arguments = Arguments::create('b', 'c');

        $this->assertSame($this->subject, $this->subject->calls($this->callbackA, $this->callbackB)->yieldsFrom(['a']));
        $this->assertSame(array('a'), iterator_to_array(call_user_func($this->answer, $this->self, $arguments)));
        $this->assertSame(array(array('b', 'c')), $this->callsA);
        $this->assertSame(array(array('b', 'c')), $this->callsB);
    }

    public function testReturns()
    {
        $this->assertSame($this->stub, $this->subject->yields('a')->yields('b')->returns());
        $this->assertSame(
            array('a', 'b'),
            iterator_to_array(call_user_func($this->answer, $this->self, $this->arguments))
        );
    }

    public function testReturnsWithExplicitNull()
    {
        $this->assertSame($this->stub, $this->subject->yields('a')->yields('b')->returns(null));
    }

    public function testReturnsWithValue()
    {
        if (!$this->featureDetector->isSupported('generator.return')) {
            $this->markTestSkipped('Requires generator return values.');
        }

        $adaptable = Phony::mock();
        $this->assertSame($this->stub, $this->subject->yields('a')->yields('b')->returns($adaptable));

        $generator = call_user_func($this->answer, $this->self, $this->arguments);

        $this->assertSame(array('a', 'b'), iterator_to_array($generator));
        $this->assertSame($adaptable->mock(), $generator->getReturn());
    }

    public function testReturnsWithInstanceHandleValue()
    {
        if (!$this->featureDetector->isSupported('generator.return')) {
            $this->markTestSkipped('Requires generator return values.');
        }

        $this->assertSame($this->stub, $this->subject->yields('a')->yields('b')->returns('c'));

        $generator = call_user_func($this->answer, $this->self, $this->arguments);

        $this->assertSame(array('a', 'b'), iterator_to_array($generator));
        $this->assertSame('c', $generator->getReturn());
    }

    public function testReturnsFailureValueNotSupported()
    {
        if ($this->featureDetector->isSupported('generator.return')) {
            $this->markTestSkipped('Requires no support for generator return values.');
        }

        $this->setExpectedException(
            'RuntimeException',
            'The current runtime does not support the supplied generator return value.'
        );
        $this->subject->returns('a');
    }

    public function testReturnsArgument()
    {
        if (!$this->featureDetector->isSupported('generator.return')) {
            $this->markTestSkipped('Requires generator return values.');
        }

        $this->assertSame($this->stub, $this->subject->yields('a')->yields('b')->returnsArgument(1));

        $arguments = Arguments::create('c', 'd');
        $generator = call_user_func($this->answer, $this->self, $arguments);

        $this->assertSame(array('a', 'b'), iterator_to_array($generator));
        $this->assertSame('d', $generator->getReturn());
    }

    public function testReturnsArgumentWithoutIndex()
    {
        if (!$this->featureDetector->isSupported('generator.return')) {
            $this->markTestSkipped('Requires generator return values.');
        }

        $this->assertSame($this->stub, $this->subject->yields('a')->yields('b')->returnsArgument());

        $arguments = Arguments::create('c', 'd');
        $generator = call_user_func($this->answer, $this->self, $arguments);

        $this->assertSame(array('a', 'b'), iterator_to_array($generator));
        $this->assertSame('c', $generator->getReturn());
    }

    public function testReturnsArgumentWithNegativeIndex()
    {
        if (!$this->featureDetector->isSupported('generator.return')) {
            $this->markTestSkipped('Requires generator return values.');
        }

        $this->assertSame($this->stub, $this->subject->yields('a')->yields('b')->returnsArgument(-1));

        $arguments = Arguments::create('c', 'd', 'e');
        $generator = call_user_func($this->answer, $this->self, $arguments);

        $this->assertSame(array('a', 'b'), iterator_to_array($generator));
        $this->assertSame('e', $generator->getReturn());
    }

    public function testReturnsArgumentWithUndefinedArgument()
    {
        if (!$this->featureDetector->isSupported('generator.return')) {
            $this->markTestSkipped('Requires generator return values.');
        }

        $this->assertSame($this->stub, $this->subject->returnsArgument(1));

        $arguments = Arguments::create();
        $generator = call_user_func($this->answer, $this->self, $arguments);
        iterator_to_array($generator);

        $this->assertNull($generator->getReturn());
    }

    public function testReturnsArgumentFailureUnsupported()
    {
        if ($this->featureDetector->isSupported('generator.return')) {
            $this->markTestSkipped('Requires no support for generator return values.');
        }

        $this->setExpectedException(
            'RuntimeException',
            'The current runtime does not support generator return values.'
        );
        $this->subject->returnsArgument();
    }

    public function testReturnsSelf()
    {
        if (!$this->featureDetector->isSupported('generator.return')) {
            $this->markTestSkipped('Requires generator return values.');
        }

        $this->assertSame($this->stub, $this->subject->yields('a')->yields('b')->returnsSelf());

        $arguments = Arguments::create('c', 'd');
        $generator = call_user_func($this->answer, $this->self, $arguments);

        $this->assertSame(array('a', 'b'), iterator_to_array($generator));
        $this->assertSame($this->self, $generator->getReturn());
    }

    public function testReturnsSelfFailureNotSupported()
    {
        if ($this->featureDetector->isSupported('generator.return')) {
            $this->markTestSkipped('Requires no support for generator return values.');
        }

        $this->setExpectedException(
            'RuntimeException',
            'The current runtime does not support generator return values.'
        );
        $this->subject->returnsSelf();
    }

    public function testThrows()
    {
        $this->subject->yields('a')->yields('b');

        $this->assertSame($this->stub, $this->subject->throws());
        $generator = call_user_func($this->answer, $this->self, $this->arguments);

        $values = array();
        $actual = null;

        try {
            foreach ($generator as $key => $value) {
                $values[$key] = $value;
            }
        } catch (Exception $actual) {
        }

        $this->assertInstanceOf('Exception', $actual);
        $this->assertSame(array('a', 'b'), $values);
    }

    public function testThrowsWithInstanceHandles()
    {
        $adaptable = Phony::mock('RuntimeException');
        $this->subject->throws($adaptable);
        $generator = call_user_func($this->answer, $this->self, $this->arguments);

        $actual = null;

        try {
            iterator_to_array($generator);
        } catch (Exception $actual) {
        }

        $this->assertSame($adaptable->mock(), $actual);
    }

    public function testThrowsWithException()
    {
        $exception = new Exception();
        $this->subject->throws($exception);
        $generator = call_user_func($this->answer, $this->self, $this->arguments);

        $actual = null;

        try {
            iterator_to_array($generator);
        } catch (Exception $actual) {
        }

        $this->assertSame($exception, $actual);
    }

    public function testThrowsWithMessage()
    {
        $exception = new Exception();
        $this->subject->throws('a');
        $generator = call_user_func($this->answer, $this->self, $this->arguments);

        $actual = null;

        try {
            iterator_to_array($generator);
        } catch (Exception $actual) {
        }

        $this->assertEquals(new Exception('a'), $actual);
    }
}