<?php

/*
 * This file is part of the Phony package.
 *
 * Copyright © 2016 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Phony\Assertion;

use Eloquent\Phony\Call\Arguments;
use Eloquent\Phony\Difference\DifferenceEngine;
use Eloquent\Phony\Exporter\InlineExporter;
use Eloquent\Phony\Invocation\InvocableInspector;
use Eloquent\Phony\Matcher\MatcherFactory;
use Eloquent\Phony\Matcher\MatcherVerifier;
use Eloquent\Phony\Mock\Builder\MockBuilderFactory;
use Eloquent\Phony\Mock\Handle\HandleFactory;
use Eloquent\Phony\Reflection\FeatureDetector;
use Eloquent\Phony\Sequencer\Sequencer;
use Eloquent\Phony\Test\TestCallFactory;
use Eloquent\Phony\Test\TestClassA;
use Eloquent\Phony\Verification\Cardinality;
use PHPUnit_Framework_TestCase;
use ReflectionClass;
use RuntimeException;

class AssertionRendererTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->invocableInspector = new InvocableInspector();
        $this->matcherVerifier = MatcherVerifier::instance();
        $this->objectSequencer = new Sequencer();
        $this->exporter = new InlineExporter(1, $this->objectSequencer);
        $this->differenceEngine = DifferenceEngine::instance();
        $this->differenceEngine->setUseColor(false);
        $this->featureDetector = FeatureDetector::instance();
        $this->subject = new AssertionRenderer(
            $this->invocableInspector,
            $this->matcherVerifier,
            $this->exporter,
            $this->differenceEngine,
            $this->featureDetector
        );
        $this->subject->setUseColor(false);
        $this->handleFactory = HandleFactory::instance();

        $this->thisObjectA = new TestClassA();

        $mockBuilderFactory = MockBuilderFactory::instance();

        $mockBuilder = $mockBuilderFactory->create('Eloquent\Phony\Test\TestClassA');
        $this->thisObjectB = $mockBuilder->get();
        $this->thisObjectBHandle = $this->handleFactory->instanceHandle($this->thisObjectB);
        $this->thisObjectBHandle->setLabel('label');
        $this->thisObjectB->testClassAMethodA();

        $mockBuilder = $mockBuilderFactory->create('IteratorAggregate');
        $mockBuilder->named('PhonyMockAssertionRendererTestIteratorAggregate');
        $this->thisObjectC = $mockBuilder->get();
        $this->thisObjectCHandle = $this->handleFactory->instanceHandle($this->thisObjectC);
        $this->thisObjectCHandle->setLabel('label');

        $this->callFactory = new TestCallFactory();
        $this->callEventFactory = $this->callFactory->eventFactory();
        $this->callA = $this->callFactory->create(
            $this->callEventFactory
                ->createCalled(array($this->thisObjectA, 'testClassAMethodA'), Arguments::create('a', 'b')),
            $this->callEventFactory->createReturned('x'),
            null,
            $this->callEventFactory->createReturned('x')
        );
        $this->callB = $this->callFactory->create(
            $this->callEventFactory->createCalled('implode'),
            $this->callEventFactory->createThrew(new RuntimeException('You done goofed.')),
            null,
            $this->callEventFactory->createThrew(new RuntimeException('You done goofed.'))
        );
        $this->callC = $this->callFactory->create(
            $this->callEventFactory->createCalled('implode')
        );
        $this->callD = $this->callFactory->create(
            $this->callEventFactory->createCalled(array($this->thisObjectB, 'testClassAMethodA'))
        );
        $this->callE = $this->callFactory->create(
            $this->callEventFactory->createCalled(array($this->thisObjectC, 'getIterator'))
        );
        $this->callF = $this->callFactory->create(
            $this->callEventFactory
                ->createCalled($this->thisObjectBHandle->testClassAMethodA, Arguments::create()),
            $this->callEventFactory->createReturned(null),
            null,
            $this->callEventFactory->createReturned(null)
        );

        $this->cardinality = new Cardinality(0, 1);

        $this->matcherFactory = MatcherFactory::instance();
    }

    public function testRenderValue()
    {
        $this->assertSame('"x"', $this->subject->renderValue('x'));
        $this->assertSame('111', $this->subject->renderValue(111));
        $this->assertSame('"x\ny"', $this->subject->renderValue("x\ny"));
        $this->assertSame(
            '"12345678901234567890123456789012345678901234567890"',
            $this->subject->renderValue('12345678901234567890123456789012345678901234567890')
        );
    }

    public function testRenderMatchers()
    {
        $matcherA = $this->matcherFactory->equalTo('a');
        $matcherB = $this->matcherFactory->equalTo(111);

        $this->assertSame('<none>', $this->subject->renderMatchers(array()));
        $this->assertSame('"a"', $this->subject->renderMatchers(array($matcherA)));
        $this->assertSame('"a", 111', $this->subject->renderMatchers(array($matcherA, $matcherB)));
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
