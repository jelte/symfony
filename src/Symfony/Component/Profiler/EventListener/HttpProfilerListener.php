<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Profiler\EventListener;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Profiler\HttpProfile;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Profiler\Profiler;

/**
 * ProfilerListener collects data for the current request by listening to the kernel events.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jelte Steijaert <jelte@khepri.be>
 */
class HttpProfilerListener extends AbstractProfilerListener
{
    protected $matcher;
    protected $onlyException;
    protected $onlyMasterRequests;
    protected $profiles;
    protected $requestStack;
    protected $parents;

    /**
     * Constructor.
     *
     * @param Profiler                     $profiler           A Profiler instance
     * @param RequestStack|null            $requestStack       A RequestStack instance. (Required in 3.0)
     * @param RequestMatcherInterface|null $matcher            A RequestMatcher instance
     * @param bool                         $onlyException      true if the profiler only collects data when an exception occurs, false otherwise
     * @param bool                         $onlyMasterRequests true if the profiler only collects data when the request is a master request, false otherwise
     */
    public function __construct(Profiler $profiler, RequestStack $requestStack, RequestMatcherInterface $matcher = null, $onlyException = false, $onlyMasterRequests = false)
    {
        parent::__construct($profiler, $onlyException);
        $this->profiler = $profiler;
        $this->requestStack = $requestStack;
        $this->matcher = $matcher;
        $this->onlyMasterRequests = (bool) $onlyMasterRequests;
        $this->profiles = new \SplObjectStorage();
        $this->parents = new \SplObjectStorage();
    }

    /**
     * Handles the onKernelException event.
     *
     * @param GetResponseForExceptionEvent $event A GetResponseForExceptionEvent instance
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($this->onlyMasterRequests && !$event->isMasterRequest()) {
            return;
        }
        $this->onException($event->getException());
    }

    /**
     * Handles the onKernelResponse event.
     *
     * @param FilterResponseEvent $event A FilterResponseEvent instance
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($this->onlyMasterRequests && !$event->isMasterRequest()) {
            return;
        }

        $exception = $this->exception;

        if ($this->onlyException && null === $this->exception) {
            return;
        }

        $this->exception = null;

        if (null !== $this->matcher && !$this->matcher->matches($event->getRequest())) {
            return;
        }

        $request = $event->getRequest();

        $profile = $this->profiler->profileRequest($request, $event->getResponse(), $exception);

        if (null === $profile) {
            return;
        }

        $this->profiles[$request] = $profile;

        $this->parents[$request] = $this->requestStack->getParentRequest();
    }

    /**
     * Handles the onKernelTerminate event.
     *
     * @param PostResponseEvent $event
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        // attach children to parents
        foreach ($this->profiles as $request) {
            // isset call should be removed when requestStack is required
            if (isset($this->parents[$request]) && null !== $parentRequest = $this->parents[$request]) {
                if (isset($this->profiles[$parentRequest])) {
                    $this->profiles[$parentRequest]->addChild($this->profiles[$request]);
                }
            }
        }

        // save profiles
        foreach ($this->profiles as $request) {
            $this->profiler->save($this->profiles[$request]);
        }

        $this->profiles = new \SplObjectStorage();
        $this->parents = new \SplObjectStorage();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => array('onKernelResponse', -100),
            KernelEvents::EXCEPTION => 'onKernelException',
            KernelEvents::TERMINATE => array('onKernelTerminate', -1024),
        );
    }
}
