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
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;

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
     * Finds profiler tokens for the given criteria.
     *
     * @param string $ip     The IP
     * @param string $url    The URL
     * @param string $limit  The maximum number of tokens to return
     * @param string $method The request method
     * @param string $start  The start date to search from
     * @param string $end    The end date to search to
     *
     * @return array An array of tokens
     *
     * @see http://php.net/manual/en/datetime.formats.php for the supported date/time formats
     */
    public function find($ip, $url, $limit, $method, $start, $end)
    {
        $criteria = array();
        if (!empty($ip)) {
            $criteria['ip'] = $ip;
        }
        if (!empty($url)) {
            $criteria['url'] = $url;
        }
        if (!empty($method)) {
            $criteria['method'] = $method;
        }

        return $this->findBy($criteria, $limit, $start, $end);
    }

    /**
     * Loads the Profile for the given Response.
     *
     * @param Response $response A Response instance
     *
     * @return ProfileInterface A Profile instance
     */
    public function loadFromResponse(Response $response)
    {
        if (!$token = $response->headers->get('X-Debug-Token')) {
            return false;
        }

        return $this->load($token);
    }

    public function profile()
    {
        $profile = parent::profile();

        if ( null !== $profile ) {
            foreach ($this->all() as $collector) {
                if ($collector instanceof DataCollectorInterface) {
                    @trigger_error(sprintf("%s implementing %s will is deprecated since version 2.8 and support for it will be removed in 3.0.", get_class($collector), 'Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface'), E_USER_DEPRECATED);

                    $clone = clone $collector;
                    if (!($clone instanceof LateDataCollectorInterface)) {
                        $request = $this->requestStack->getCurrentRequest();
                        $clone->collect($request, $this->responses[$request]);
                    }
                    // we need to clone for sub-requests
                    $profile->addCollector($clone);
                }
            }
        }

        return $profile;
    }

    protected function createProfile()
    {
        if (null === ($request = $this->requestStack->getCurrentRequest())) {
            return;
        }

        if (!isset($this->responses[$request])) {
            return;
        }
        $response = $this->responses[$request];

        $profile = new HttpProfile(
            $this->generateToken(),
            $request->getClientIp(),
            $request->getUri(),
            $request->getMethod(),
            $response->getStatusCode()
        );

        $response->headers->set('X-Debug-Token', $profile->getToken());

        return $profile;
    }

    public function addResponse(Request $request, Response $response)
    {
        $this->responses[$request] = $response;
    }
}
