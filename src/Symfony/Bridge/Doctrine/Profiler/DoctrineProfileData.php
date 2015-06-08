<?php

namespace Symfony\Bridge\Doctrine\Profiler;

use Symfony\Component\Profiler\ProfileData\ProfileDataInterface;

class DoctrineProfileData implements ProfileDataInterface
{
    private $queries;
    private $connections;
    private $managers;

    public function __construct(array $queries, array $connections, array $managers)
    {
        $this->queries = $queries;
        $this->connections = $connections;
        $this->managers = $managers;
    }

    public function getManagers()
    {
        return $this->managers;
    }

    public function getConnections()
    {
        return $this->connections;
    }

    public function getQueryCount()
    {
        return array_sum(array_map('count', $this->queries));
    }

    public function getQueries()
    {
        return $this->queries;
    }

    public function getTime()
    {
        $time = 0;
        foreach ($this->queries as $queries) {
            foreach ($queries as $query) {
                $time += $query['executionMS'];
            }
        }

        return $time;
    }
}