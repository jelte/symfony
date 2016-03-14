<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\WebProfilerBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Profiler\Storage\ProfilerStorageInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\Matcher\TraceableUrlMatcher;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\DataCollector\RequestDataCollector;
use Symfony\Component\Profiler\Profiler;

/**
 * RouterController.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class RouterController
{
    private $profiler;
    private $profilerStorage;
    private $twig;
    private $matcher;
    private $routes;

    public function __construct(Profiler $profiler = null, ProfilerStorageInterface $profilerStorage = null, \Twig_Environment $twig,
                                UrlMatcherInterface $matcher = null, RouteCollection $routes = null)
    {
        $this->profiler = $profiler;
        $this->profilerStorage = $profilerStorage;
        $this->twig = $twig;
        $this->matcher = $matcher;
        $this->routes = (null === $routes && $matcher instanceof RouterInterface) ? $matcher->getRouteCollection() : $routes;
    }

    /**
     * Renders the profiler panel for the given token.
     *
     * @param string $token The profiler token
     *
     * @return Response A Response instance
     *
     * @throws NotFoundHttpException
     */
    public function panelAction($token)
    {
        if (null === $this->profiler || null === $this->profilerStorage) {
            throw new NotFoundHttpException('The profiler must be enabled.');
        }

        $this->profiler->disable();

        if (null === $this->matcher || null === $this->routes) {
            return new Response('The Router is not enabled.', 200, array('Content-Type' => 'text/html'));
        }

        $profile = $this->profilerStorage->read($token);

        /** @var RequestDataCollector $request */
        $request = $profile->get('request');

        return new Response($this->twig->render('@WebProfiler/Router/panel.html.twig', array(
            'request' => $request,
            'router' => $profile->get('router'),
            'traces' =>  $this->getTraces($request, $profile->getMethod())
        )), 200, array('Content-Type' => 'text/html'));
    }

    /**
     * Returns the routing traces associated to the given request.
     *
     * @param RequestDataCollector $request
     * @param string               $method
     *
     * @return array
     */
    private function getTraces(RequestDataCollector $request, $method)
    {
        $traceRequest = Request::create(
            $request->getPathInfo(),
            $request->getRequestServer()->get('REQUEST_METHOD'),
            $request->getRequestAttributes()->all(),
            $request->getRequestCookies()->all(),
            array(),
            $request->getRequestServer()->all()
        );

        $context = $this->matcher->getContext();
        $context->setMethod($method);
        $matcher = new TraceableUrlMatcher($this->routes, $context);

        return $matcher->getTracesForRequest($traceRequest);
    }
}
