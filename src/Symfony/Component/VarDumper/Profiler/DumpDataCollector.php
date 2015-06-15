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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Profiler\DataCollector\AbstractDataCollector;
use Symfony\Component\Profiler\DataCollector\RuntimeDataCollectorInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Dumper\DataDumperInterface;

/**
 * Class DumpDataCollector.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 * @author Jelte Steijaert <jelte@khepri.be>
 */
class DumpDataCollector extends AbstractDataCollector implements RuntimeDataCollectorInterface
{
    private $data = array();
    private $requestStack;
    private $stopwatch;
    private $fileLinkFormat;
    private $dataCount = 0;
    private $charset;
    private $responses;

    public function __construct(RequestStack $requestStack, Stopwatch $stopwatch = null, $fileLinkFormat = null, $charset = null)
    {
        $this->requestStack = $requestStack;
        $this->stopwatch = $stopwatch;
        $this->fileLinkFormat = $fileLinkFormat ?: ini_get('xdebug.file_link_format') ?: get_cfg_var('xdebug.file_link_format');
        $this->charset = $charset ?: ini_get('php.output_encoding') ?: ini_get('default_charset') ?: 'UTF-8';
        $this->responses = new \SplObjectStorage();
    }

    public function addDump(Data $data, $name, $file, $line, $fileExcerpt)
    {
        $this->data[] = compact('data', 'name', 'file', 'line', 'fileExcerpt');
    }

    public function collect()
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return;
        }

        // Sub-requests and programmatic calls stay in the collected profile.
        if ($this->requestStack->getMasterRequest() !== $request || $request->isXmlHttpRequest() || $request->headers->has('Origin')) {
            return;
        }

        return new DumpData($this->data, $this->dataCount, $this->charset);
    }

    public function getName()
    {
        return 'dump';
    }
}
