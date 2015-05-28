<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Profiler\EventListener\HttpProfilerListener;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

/**
 * ProfilerListener collects data for the current request by listening to the kernel events.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @deprecated since 2.8, to be removed in 3.0. Use Symfony\Component\Profiler\EventListener\ProfileListener instead.
 */
class ProfilerListener extends HttpProfilerListener
{
    /**
     * Constructor.
     *
     * @param Profiler                     $profiler           A Profiler instance
     * @param RequestMatcherInterface|null $matcher            A RequestMatcher instance
     * @param bool                         $onlyException      true if the profiler only collects data when an exception occurs, false otherwise
     * @param bool                         $onlyMasterRequests true if the profiler only collects data when the request is a master request, false otherwise
     * @param RequestStack|null            $requestStack       A RequestStack instance
     */
    public function __construct(Profiler $profiler, RequestMatcherInterface $matcher = null, $onlyException = false, $onlyMasterRequests = false, RequestStack $requestStack = null)
    {
        if (null === $requestStack) {
            // Prevent the deprecation notice to be triggered all the time.
            // The onKernelRequest() method fires some logic only when the
            // RequestStack instance is not provided as a dependency.
            trigger_error('Since version 2.4, the '.__METHOD__.' method must accept a RequestStack instance to get the request instead of using the '.__CLASS__.'::onKernelRequest method that will be removed in 3.0.', E_USER_DEPRECATED);
        }

        parent::__construct($profiler, $requestStack, $matcher, $onlyException, $onlyMasterRequests);
    }

    /**
     * @deprecated since version 2.4, to be removed in 3.0.
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (null === $this->requestStack) {
            $this->requests[] = $event->getRequest();
        }
    }

    public static function getSubscribedEvents()
    {
        return array_merge(array(
            // kernel.request must be registered as early as possible to not break
            // when an exception is thrown in any other kernel.request listener
            KernelEvents::REQUEST => array('onKernelRequest', 1024),
        ), parent::getSubscribedEvents());
    }
}