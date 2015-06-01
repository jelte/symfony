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

class EventDataCollectorTest extends \PHPUnit_Framework_TestCase
{
    public function testCollect()
    {
        $c = new EventDataCollector(new TraceableEventDispatcher(new EventDispatcher(), new Stopwatch()));

        $this->assertEquals('events', $c->getName());

        $data = $c->lateCollect();
        $this->assertInstanceof('Symfony\Component\Profiler\ProfileData\EventData', $data);
    }

    public function testCollectWithoutEventDispatcher()
    {
        $c = new EventDataCollector(null);

        $data = $c->lateCollect();
        $this->assertNull($data);
    }
}
