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
 * RuntimeDataCollectorInterface.
 *
 * @author Jelte Steijaert <jelte@khepri.be>
 */
interface RuntimeDataCollectorInterface
{
    /**
     * Collects data when profiler is triggered.
     *
     * @return ProfileDataInterface
     */
    public function collect();
}
