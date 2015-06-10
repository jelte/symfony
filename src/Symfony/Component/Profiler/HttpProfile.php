<?php

namespace Symfony\Component\Profiler;

class HttpProfile extends AbstractProfile
{
    protected $ip;
    protected $method;
    protected $url;
    protected $statusCode;

    public function __construct($token, $ip, $url, $method, $statusCode, $time = null)
    {
        parent::__construct($token, $time);
        $this->ip = $ip;
        $this->url = $url;
        $this->method = $method;
        $this->statusCode = $statusCode;
    }

    /**
     * Returns the IP.
     *
     * @return string The IP
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Returns the request method.
     *
     * @return string The request method
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Returns the URL.
     *
     * @return string The URL
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
