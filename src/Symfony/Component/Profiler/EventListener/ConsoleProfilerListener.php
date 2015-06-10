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

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Profiler\ConsoleProfiler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * ProfilerListener collects data for the current request by listening to the kernel events.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jelte Steijaert <jelte@khepri.be>
 */
class ConsoleProfilerListener implements EventSubscriberInterface
{
    protected $profiler;
    protected $onlyException;
    protected $exception;

    /**
     * Constructor.
     *
     * @param ConsoleProfiler $profiler      A Profiler instance
     * @param bool            $onlyException true if the profiler only collects data when an exception occurs, false otherwise
     */
    public function __construct(ConsoleProfiler $profiler, $onlyException = false)
    {
        $this->profiler = $profiler;
        $this->onlyException = (bool) $onlyException;
        $this->commands = new \SplObjectStorage();
    }

    /**
     * Handles the onConsoleException event.
     *
     * @param ConsoleExceptionEvent $event A ConsoleExceptionEvent instance
     */
    public function onConsoleException(ConsoleExceptionEvent $event)
    {
        $this->exception = $event->getException();
    }

    /**
     * Handles the onConsoleTerminate event.
     *
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        if ($this->onlyException && null === $this->exception) {
            return;
        }

        $this->exception = null;

        $this->profiler->addCommand($event->getCommand(), $event->getInput(), $event->getExitCode());

        $profile = $this->profiler->profile();

        $this->profiler->save($profile);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            ConsoleEvents::EXCEPTION => 'onConsoleException',
            ConsoleEvents::TERMINATE => array('onConsoleTerminate', -1024),
        );
    }
}
