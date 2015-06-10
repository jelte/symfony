<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\VarDumper\Profiler;

use Symfony\Component\Profiler\ProfileData\AbstractProfileData;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

/**
 * Class DumpData.
 *
 * @author Jelte Steijaert <jelte@khepri.be>
 */
class DumpData extends AbstractProfileData
{
    private $dataCount = 0;
    private $charset;

    public function __construct(array $data, $dataCount, $charset)
    {
        parent::__construct($data);
        $this->dataCount = $dataCount;
        $this->charset = $charset;
    }

    /**
     * Returns the number of dumps
     *
     * @return int
     */
    public function getDumpsCount()
    {
        return $this->dataCount;
    }

    /**
     * Return the collected dumps in a specific format.
     *
     * @param $format
     * @param int $maxDepthLimit
     * @param int $maxItemsPerDepth
     * @return array
     */
    public function getDumps($format, $maxDepthLimit = -1, $maxItemsPerDepth = -1)
    {
        $data = fopen('php://memory', 'r+b');

        if ('html' === $format) {
            $dumper = new HtmlDumper($data, $this->charset);
        } else {
            throw new \InvalidArgumentException(sprintf('Invalid dump format: %s', $format));
        }
        $dumps = array();

        foreach ($this->data as $dump) {
            if (method_exists($dump['data'], 'withMaxDepth')) {
                $dumper->dump($dump['data']->withMaxDepth($maxDepthLimit)->withMaxItemsPerDepth($maxItemsPerDepth));
            } else {
                // getLimitedClone is @deprecated, to be removed in 3.0
                $dumper->dump($dump['data']->getLimitedClone($maxDepthLimit, $maxItemsPerDepth));
            }
            rewind($data);
            $dump['data'] = stream_get_contents($data);
            ftruncate($data, 0);
            rewind($data);
            $dumps[] = $dump;
        }

        return $dumps;
    }
}
