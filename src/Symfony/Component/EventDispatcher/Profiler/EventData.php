<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\EventDispatcher\Profiler;

use Symfony\Component\Profiler\ProfileData\ProfileDataInterface;

/**
 * Class EventData.
 *
 * @author Jelte Steijaert <jelte@khepri.be>
 */
class EventData implements ProfileDataInterface
{
    private $calledListeners;
    private $notCalledListeners;

    public function __construct(array $calledListeners = array(), array $notCalledListeners = array())
    {
        $this->calledListeners = $this->flatten($calledListeners);
        $this->notCalledListeners = $this->flatten($notCalledListeners);
    }

    private function flatten(array $data)
    {
        $flattened = array();
        foreach ( $data as $eventName => $listeners ) {
            foreach ( $listeners as $priority => $l ) {
                foreach ($l as $pretty => $listener) {
                    $flattened[$eventName . '.' . $pretty] = $listener;
                }
            }
        }

        return $flattened;
    }

    /**
     * Gets the called listeners.
     *
     * @return array An array of called listeners
     *
     * @see TraceableEventDispatcherInterface
     */
    public function getCalledListeners()
    {
        return $this->calledListeners;
    }

    /**
     * Gets the not called listeners.
     *
     * @return array An array of not called listeners
     *
     * @see TraceableEventDispatcherInterface
     */
    public function getNotCalledListeners()
    {
        return $this->notCalledListeners;
    }
}
