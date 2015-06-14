<?php


namespace Symfony\Component\Profiler\EventListener;


use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Profiler\Profiler;

abstract class AbstractProfilerListener implements EventSubscriberInterface
{
    protected $profiler;
    protected $onlyException = false;
    protected $exception;

    public function __construct(Profiler $profiler, $onlyException = false)
    {
        $this->profiler = $profiler;
        $this->onlyException = (bool) $onlyException;
    }

    protected function onException(\Exception $exception)
    {
        $this->exception = $exception;
    }
}