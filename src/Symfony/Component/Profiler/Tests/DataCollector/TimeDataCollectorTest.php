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

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Profiler\DataCollector\TimeDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Stopwatch\Stopwatch;

class TimeDataCollectorTest extends \PHPUnit_Framework_TestCase
{
    public function testCollect()
    {
        $c = new TimeDataCollector();
        $this->assertSame('time', $c->getName());

        $c->setToken('Mock-Test-Token');

        $data = $c->lateCollect();
        $this->assertEquals(0, $data->getStartTime());
        $this->assertEquals(0, $data->getInitTime());
        $this->assertEquals(0, $data->getDuration());
    }

    public function testCollectWithStopwatch()
    {
        $requestStack = new RequestStack();
        $stopwatch = new Stopwatch();

        $c = new TimeDataCollector($stopwatch);
        $token = 'Mock-Test-Token-Stopwatch';
        $stopwatch->openSection();
        $stopwatch->start('Kernel.Request', 'section');
        sleep(1);
        $stopwatch->stop('Kernel.Request');
        $stopwatch->stopSection($token);

        $request = new Request();
        $requestStack->push($request);

        $c->setToken($token);

        $data = $c->lateCollect();
        $this->assertGreaterThan(1, $data->getDuration() / 1000);
        $this->assertInternalType('array', $data->getEvents());
        $this->assertGreaterThan(0, $data->getInitTime());
    }
}
