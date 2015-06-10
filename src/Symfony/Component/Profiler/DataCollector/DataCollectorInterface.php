<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Profiler\DataCollector;

/**
 * DataCollectorInterface.
 *
 * @author Jelte Steijaert <jelte@khepri.be>
 */
interface DataCollectorInterface
{
    public function setToken($token);

    /**
     * Returns the name of the collector.
     *
     * @return string The collector name
     *
     * @api
     */
    public function getName();
}
