<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Profiler\Tests\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Profiler\EventListener\HttpProfilerListener;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Kernel;

class HttpProfilerListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test a master and sub request with an exception and `onlyException` profiler option enabled.
     */
    public function testKernelTerminate()
    {
        $profile = $this->getMockBuilder('Symfony\Component\Profiler\HttpProfile')
            ->disableOriginalConstructor()
            ->getMock();

        $profiler = $this->getMockBuilder('Symfony\Component\Profiler\HttpProfiler')
            ->disableOriginalConstructor()
            ->getMock();

        $profiler->expects($this->once())
            ->method('profile')
            ->will($this->returnValue($profile));

        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');

        $masterRequest = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $subRequest = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $subRequest2 = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $requestStack = new RequestStack();
        $requestStack->push($masterRequest);
        $requestStack->push($subRequest);
        $requestStack->push($subRequest2);

        $onlyException = true;
        $listener = new HttpProfilerListener($profiler, $requestStack, null, $onlyException, false);

        // master request
        $listener->onKernelResponse(new FilterResponseEvent($kernel, $masterRequest, Kernel::MASTER_REQUEST, $response));

        // sub request
        $listener->onKernelException(new GetResponseForExceptionEvent($kernel, $subRequest, Kernel::SUB_REQUEST, new HttpException(404)));
        $listener->onKernelResponse(new FilterResponseEvent($kernel, $subRequest, Kernel::SUB_REQUEST, $response));

        $listener->onKernelTerminate(new PostResponseEvent($kernel, $masterRequest, $response));
    }

    /**
     * Test a master and sub request with an exception and `onlyException` profiler option enabled.
     */
    public function testKernelExceptionOnlyMasterWithSub()
    {
        $profiler = $this->getMockBuilder('Symfony\Component\Profiler\HttpProfiler')
            ->disableOriginalConstructor()
            ->getMock();

        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');

        $subRequest = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $requestStack = new RequestStack();

        $onlyException = true;
        $listener = new HttpProfilerListener($profiler, $requestStack, null, $onlyException, true);

        // sub request
        $listener->onKernelException(new GetResponseForExceptionEvent($kernel, $subRequest, Kernel::SUB_REQUEST, new HttpException(404)));
    }

    /**
     * Test a master and sub request with an exception and `onlyException` profiler option enabled.
     */
    public function testKernelResponseOnlyMasterWithSub()
    {
        $profiler = $this->getMockBuilder('Symfony\Component\Profiler\HttpProfiler')
            ->disableOriginalConstructor()
            ->getMock();

        $profiler->expects($this->never())
            ->method('profile');

        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');

        $subRequest = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $requestStack = new RequestStack();

        $listener = new HttpProfilerListener($profiler, $requestStack, null, false, true);

        // sub request
        $listener->onKernelResponse(new FilterResponseEvent($kernel, $subRequest, Kernel::SUB_REQUEST, $response));
    }

    /**
     * Test a master and sub request with an exception and `onlyException` profiler option enabled.
     */
    public function testKernelResponseNoProfileReturned()
    {
        $profiler = $this->getMockBuilder('Symfony\Component\Profiler\HttpProfiler')
            ->disableOriginalConstructor()
            ->getMock();

        $profiler->expects($this->once())
            ->method('profile')
            ->will($this->returnValue(null));

        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');

        $masterRequest = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $requestStack = new RequestStack();
        $requestStack->push($masterRequest);

        $listener = new HttpProfilerListener($profiler, $requestStack, null, false, false);

        // master request
        $listener->onKernelResponse(new FilterResponseEvent($kernel, $masterRequest, Kernel::MASTER_REQUEST, $response));
    }

    /**
     * Test a master and sub request with an exception and `onlyException` profiler option enabled.
     */
    public function testKernelResponseWithMatcher()
    {
        $profiler = $this->getMockBuilder('Symfony\Component\Profiler\HttpProfiler')
            ->disableOriginalConstructor()
            ->getMock();

        $profiler->expects($this->never())
            ->method('profile');

        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');

        $matcher = $this->getMock('Symfony\Component\HttpFoundation\RequestMatcherInterface');
        $matcher->expects($this->once())
            ->method('matches')
            ->willReturn(false);

        $masterRequest = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $requestStack = new RequestStack();
        $requestStack->push($masterRequest);

        $listener = new HttpProfilerListener($profiler, $requestStack, $matcher, false, false);

        // master request
        $listener->onKernelResponse(new FilterResponseEvent($kernel, $masterRequest, Kernel::MASTER_REQUEST, $response));
    }

    public function testSubscribedEvents()
    {
        $events = HttpProfilerListener::getSubscribedEvents();
        $this->assertArrayHasKey(KernelEvents::TERMINATE, $events);
        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);
    }
}
