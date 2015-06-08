<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Profiler\Tests\DataCollector;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher;
use Symfony\Component\Profiler\DataCollector\EventDataCollector;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Profiler\ProfileData\EventData;

class EventDataCollectorTest extends \PHPUnit_Framework_TestCase
{
    public function testCollect()
    {
        $dispatcher = new TraceableEventDispatcher(new EventDispatcher(), new Stopwatch());

        $dispatcher->addListener('test', function() { });

        $c = new EventDataCollector($dispatcher);

        $this->assertEquals('events', $c->getName());

        /** @var EventData $data */
        $data = $c->lateCollect();
        $this->assertInstanceof('Symfony\Component\Profiler\ProfileData\EventData', $data);
        $this->assertCount(0, $data->getCalledListeners());
        $this->assertCount(1, $data->getNotCalledListeners());

        $dispatcher->dispatch('test');

        $data = $c->lateCollect();
        $this->assertCount(1, $data->getCalledListeners());
        $this->assertCount(0, $data->getNotCalledListeners());
    }

    public function testCollectWithoutEventDispatcher()
    {
        $c = new EventDataCollector(null);

        $data = $c->lateCollect();
        $this->assertNull($data);
    }
}
