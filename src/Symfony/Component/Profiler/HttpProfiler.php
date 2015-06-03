<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Profiler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\Profiler\Storage\ProfilerStorageInterface;

/**
 * Profiler.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jelte Steijaert <jelte@khepri.be>
 */
class HttpProfiler extends AbstractProfiler
{
    protected $requestStack;
    protected $responses;

    public function __construct(RequestStack $requestStack, ProfilerStorageInterface $storage, LoggerInterface $logger = null)
    {
        parent::__construct($storage, $logger);
        $this->requestStack = $requestStack;
        $this->responses = new \SplObjectStorage();
    }

    /**
     * Loads the Profile for the given Response.
     *
     * @param Response $response A Response instance
     *
     * @return Profile A Profile instance
     */
    public function loadFromResponse(Response $response)
    {
        if (!$token = $response->headers->get('X-Debug-Token')) {
            return false;
        }

        return $this->load($token);
    }

    /**
     * Collects data for the given Response.
     *
     * @return Profile|null A Profile instance or null if the profiler is disabled
     */
    public function profile()
    {
        if ( null === $this->requestStack->getCurrentRequest() ) {
            return;
        }

        return parent::profile();
    }

    protected function createProfile()
    {
        $request = $this->requestStack->getCurrentRequest();
        $response = $this->responses[$request];

        $profile = new Profile(substr(hash('sha256', uniqid(mt_rand(), true)), 0, 6));
        $profile->setTime(time());
        $profile->setUrl($request->getUri());
        $profile->setIp($request->getClientIp());
        $profile->setMethod($request->getMethod());
        $profile->setStatusCode($response->getStatusCode());

        $response->headers->set('X-Debug-Token', $profile->getToken());

        return $profile;
    }

    public function addResponse(Request $request, Response $response)
    {
        $this->responses[$request] = $response;
    }
}
