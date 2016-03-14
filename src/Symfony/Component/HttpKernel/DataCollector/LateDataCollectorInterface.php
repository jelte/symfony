<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\DataCollector;

use Symfony\Component\Profiler\DataCollector\LateDataCollectorInterface as BaseLateDataCollectorInterface;

/**
 * LateDataCollectorInterface.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @deprecated since 2.8, to be removed in 3.0. Use Symfony\Component\Profiler\DataCollector\LateDataCollectorInterface instead.
 */
interface LateDataCollectorInterface extends BaseLateDataCollectorInterface
{
    /**
     * Collects data as late as possible.
     */
    public function lateCollect();
}
