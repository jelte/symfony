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
use Symfony\Component\Profiler\ConsoleProfile;

/**
 * ProfilerListener collects data for the current request by listening to the kernel events.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jelte Steijaert <jelte@khepri.be>
 */
class ConsoleProfilerListener extends AbstractProfilerListener
{
    /**
     * Handles the onConsoleException event.
     *
     * @param ConsoleExceptionEvent $event A ConsoleExceptionEvent instance
     */
    public function onConsoleException(ConsoleExceptionEvent $event)
    {
        $this->onException($event->getException());
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

        $profile = $this->profiler->profileCommand($event->getCommand(), $event->getInput(), $event->getExitCode());

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
