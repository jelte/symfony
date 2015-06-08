<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\DataCollector;

use Symfony\Component\Profiler\DataCollector\AbstractDataCollector;

/**
 * AjaxDataCollector.
 *
 * @author Bart van den Burg <bart@burgov.nl>
 */
class AjaxDataCollector extends AbstractDataCollector
{
    public function getName()
    {
        return 'ajax';
    }
}
