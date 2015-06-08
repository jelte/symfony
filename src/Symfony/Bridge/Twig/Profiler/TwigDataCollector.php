<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Twig\Profiler;

use Symfony\Component\Profiler\DataCollector\AbstractDataCollector;
use Symfony\Component\Profiler\DataCollector\LateDataCollectorInterface;

/**
 * TwigDataCollector.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TwigDataCollector extends AbstractDataCollector implements LateDataCollectorInterface
{
    private $profile;

    public function __construct(\Twig_Profiler_Profile $profile)
    {
        $this->profile = $profile;
    }

    /**
     * {@inheritdoc}
     */
    public function lateCollect()
    {
        return new TwigProfileData($this->profile);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'twig';
    }
}
