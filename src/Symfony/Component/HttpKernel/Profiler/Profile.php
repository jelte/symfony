<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Profiler;

use Symfony\Component\Profiler\HttpProfile;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

/**
 * Profile.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @deprecated since 2.8, to be removed in 3.0. Use Symfony\Component\Profiler\HttpProfile instead.
 */
class Profile extends HttpProfile
{
    /**
     * @param string $token The Token
     */
    public function __construct($token)
    {
        parent::__construct($token, null, null, null, null, null);
    }

    /**
     * Sets the token.
     *
     * @param string $token The token
     *
     * @deprecated since 2.8, Profile will be immutable in 3.0.
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Sets the IP.
     *
     * @param string $ip
     *
     * @deprecated since 2.8, Profile will be immutable in 3.0.
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * Sets the Method.
     *
     * @param string $method
     *
     * @deprecated since 2.8, Profile will be immutable in 3.0.
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * Sets the URL.
     *
     * @param string $url
     *
     * @deprecated since 2.8, Profile will be immutable in 3.0.
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Sets the time.
     *
     * @param int $time
     *
     * @deprecated since 2.8, Profile will be immutable in 3.0.
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    /**
     * Sets the StatusCode.
     *
     * @param int $statusCode
     *
     * @deprecated since 2.8, Profile will be immutable in 3.0.
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * Sets the Collectors associated with this profile.
     *
     * @param DataCollectorInterface[] $collectors
     */
    public function setCollectors(array $collectors)
    {
        $this->collectors = array();
        foreach ($collectors as $collector) {
            $this->addCollector($collector);
        }
    }


    /**
     * Gets a Collector by name.
     *
     * @param string $name A collector name
     *
     * @return DataCollectorInterface A DataCollectorInterface instance
     *
     * @throws \InvalidArgumentException if the collector does not exist
     */
    public function getCollector($name)
    {
        if (!isset($this->collectors[$name])) {
            throw new \InvalidArgumentException(sprintf('Collector "%s" does not exist.', $name));
        }

        return $this->collectors[$name];
    }

    /**
     * Returns true if a Collector for the given name exists.
     *
     * @param string $name A collector name
     *
     * @return bool
     */
    public function hasCollector($name)
    {
        return isset($this->collectors[$name]);
    }
}
