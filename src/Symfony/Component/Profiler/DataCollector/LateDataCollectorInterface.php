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

use Symfony\Component\Profiler\ProfileData\ProfileDataInterface;

/**
 * LateDataCollectorInterface.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface LateDataCollectorInterface
{
    /**
     * Collects data as late as possible.
     *
     * @return ProfileDataInterface
     */
    public function lateCollect();
}
