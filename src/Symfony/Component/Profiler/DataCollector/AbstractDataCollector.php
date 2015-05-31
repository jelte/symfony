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
 * DataCollector.
 *
 * Children of this class must store the collected data in the data property.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Bernhard Schussek <bschussek@symfony.com>
 * @author Jelte Steijaert  <jelte@khepri.be>
 */
abstract class AbstractDataCollector implements DataCollectorInterface
{
    protected $token;

    public function setToken($token)
    {
        $this->token = $token;
    }
}
