<?php

/*
 * This file is part of the Phony package.
 *
 * Copyright © 2016 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Phony\Event;

use Eloquent\Phony\Call\Arguments;
use Eloquent\Phony\Test\TestCallFactory;
use PHPUnit_Framework_TestCase;

class EventSequenceTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->callFactory = new TestCallFactory();
        $this->callEventFactory = $this->callFactory->eventFactory();
        $this->eventA = $this->callEventFactory->createReturned(null);
        $this->eventB =
            $this->callFactory->create($this->callEventFactory->createCalled(null, Arguments::create('a', 'b')));
        $this->eventC = $this->callEventFactory->createCalled(null, Arguments::create('c', 'd'));
        $this->eventD =
            $this->callFactory->create($this->callEventFactory->createCalled(null, Arguments::create('e', 'f')));
        $this->events = array($this->eventA, $this->eventB, $this->eventC, $this->eventD);
        $this->subject = new EventSequence($this->events);
    }

    public function testConstructor()
    {
        $this->assertTrue($this->subject->hasEvents());
        $this->assertTrue($this->subject->hasCalls());
        $this->assertSame($this->events, $this->subject->allEvents());
        $this->assertSame(array($this->eventB, $this->eventD), $this->subject->allCalls());
        $this->assertSame(2, $this->subject->callCount());
        $this->assertSame(4, $this->subject->eventCount());
        $this->assertSame(4, count($this->subject));
    }

    public function testConstructorDefaults()
    {
        $this->subject = new EventSequence(array());

        $this->assertFalse($this->subject->hasEvents());
        $this->assertFalse($this->subject->hasCalls());
        $this->assertSame(array(), $this->subject->allEvents());
        $this->assertSame(array(), $this->subject->allCalls());
        $this->assertSame(0, $this->subject->callCount());
        $this->assertSame(0, $this->subject->eventCount());
        $this->assertSame(0, count($this->subject));
    }

    public function testFirstEvent()
    {
        $this->assertSame($this->eventA, $this->subject->firstEvent());
    }

    public function testFirstEventFailureUndefined()
    {
        $this->subject = new EventSequence(array());

        $this->setExpectedException('Eloquent\Phony\Event\Exception\UndefinedEventException');
        $this->subject->firstEvent();
    }

    public function testLastEvent()
    {
        $this->assertSame($this->eventD, $this->subject->lastEvent());
    }

    public function testLastEventFailureUndefined()
    {
        $this->subject = new EventSequence(array());

        $this->setExpectedException('Eloquent\Phony\Event\Exception\UndefinedEventException');
        $this->subject->lastEvent();
    }

    public function testEventAt()
    {
        $this->assertSame($this->eventA, $this->subject->eventAt());
        $this->assertSame($this->eventA, $this->subject->eventAt(0));
        $this->assertSame($this->eventB, $this->subject->eventAt(1));
        $this->assertSame($this->eventB, $this->subject->eventAt(-3));
    }

    public function testEventAtFailure()
    {
        $this->setExpectedException('Eloquent\Phony\Event\Exception\UndefinedEventException');
        $this->subject->eventAt(111);
    }

    public function testFirstCall()
    {
        $this->assertSame($this->eventB, $this->subject->firstCall());
    }

    public function testFirstCallFailureUndefined()
    {
        $this->subject = new EventSequence(array());

        $this->setExpectedException('Eloquent\Phony\Call\Exception\UndefinedCallException');
        $this->subject->firstCall();
    }

    public function testLastCall()
    {
        $this->assertSame($this->eventD, $this->subject->lastCall());
    }

    public function testLastCallFailureUndefined()
    {
        $this->subject = new EventSequence(array());

        $this->setExpectedException('Eloquent\Phony\Call\Exception\UndefinedCallException');
        $this->subject->lastCall();
    }

    public function testCallAt()
    {
        $this->assertSame($this->eventB, $this->subject->callAt());
        $this->assertSame($this->eventB, $this->subject->callAt(0));
        $this->assertSame($this->eventD, $this->subject->callAt(1));
        $this->assertSame($this->eventD, $this->subject->callAt(-1));
    }

    public function testCallAtFailure()
    {
        $this->setExpectedException('Eloquent\Phony\Call\Exception\UndefinedCallException');
        $this->subject->callAt(111);
    }

    public function testIteration()
    {
        $this->assertSame($this->events, iterator_to_array($this->subject));
    }
}
