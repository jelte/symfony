<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Debug\Profiler;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Profiler\DataCollector\AbstractDataCollector;
use Symfony\Component\Profiler\DataCollector\RuntimeDataCollectorInterface;

/**
 * ExceptionDataCollector.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ExceptionDataCollector extends AbstractDataCollector implements RuntimeDataCollectorInterface, EventSubscriberInterface
{
    private $exception;

    /**
     * {@inheritdoc}
     */
    public function collect()
    {
        if (null === $this->exception) {
            return;
        }

        $exception = FlattenException::create($this->exception);
        $this->exception = null;

        return new ExceptionData($exception);
    }

    /**
     * Handles the onException event.
     *
     * @param Event $event
     */
    public function onException(Event $event)
    {
        if (method_exists($event, 'getException')) {
            $this->exception = $event->getException();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'exception';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $events = array();

        if (defined('Symfony\Component\HttpKernel\KernelEvents::COMMAND')) {
            $events[KernelEvents::EXCEPTION] = array('onException');
        }
        if (defined('Symfony\Component\Console\ConsoleEvents::COMMAND')) {
            $events[ConsoleEvents::EXCEPTION] = array('onException');
        }

        return $events;
    }
}
