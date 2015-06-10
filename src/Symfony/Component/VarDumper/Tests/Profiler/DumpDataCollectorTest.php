<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\VarDumper\Tests\Profiler;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\VarDumper\Profiler\DumpData;
use Symfony\Component\VarDumper\Profiler\DumpDataCollector;
use Symfony\Component\VarDumper\Tests\Profiler\Mock\TwigTemplate;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
class DumpDataCollectorTest extends \PHPUnit_Framework_TestCase
{
    public function testDump()
    {
        $data = new Data(array(array(123)));

        $collector = new DumpDataCollector(new RequestStack(), new Stopwatch());

        $this->assertSame('dump', $collector->getName());
        $collector->dump($data);
        $line = __LINE__ - 1;
        $this->assertSame(1, $collector->getDumpsCount());

        $dump = $collector->getDumps('html');
        $this->assertTrue(isset($dump[0]['data']));
        $dump[0]['data'] = preg_replace('/^.*?<pre/', '<pre', $dump[0]['data']);
        $dump[0]['data'] = preg_replace('/sf-dump-\d+/', 'sf-dump', $dump[0]['data']);

        $xDump = array(
            array(
                'data' => "<pre class=sf-dump id=sf-dump data-indent-pad=\"  \"><span class=sf-dump-num>123</span>\n</pre><script>Sfdump(\"sf-dump\")</script>\n",
                'name' => 'DumpDataCollectorTest.php',
                'file' => __FILE__,
                'line' => $line,
                'fileExcerpt' => false,
            ),
        );
        $this->assertSame($xDump, $dump);

        $this->assertStringMatchesFormat(
            'a:1:{i:0;a:5:{s:4:"data";O:39:"Symfony\Component\VarDumper\Cloner\Data":4:{s:45:"Symfony\Component\VarDumper\Cloner\Datadata";a:1:{i:0;a:1:{i:0;i:123;}}s:49:"Symfony\Component\VarDumper\Cloner\DatamaxDepth";i:%i;s:57:"Symfony\Component\VarDumper\Cloner\DatamaxItemsPerDepth";i:%i;s:54:"Symfony\Component\VarDumper\Cloner\DatauseRefHandles";i:%i;}s:4:"name";s:25:"DumpDataCollectorTest.php";s:4:"file";s:%a',
            str_replace("\0", '', $collector->serialize())
        );

        $this->assertSame(0, $collector->getDumpsCount());
        $this->assertSame('a:0:{}', $collector->serialize());
    }

    public function testDumpExtensive()
    {
        $data = new Data(array(array(123)));

        $collector = new DumpDataCollector(new RequestStack(), new Stopwatch());
        $dumper = new VarDumper();
        $dumper->setHandler(array($collector, 'dump'));
        ob_start();
        $dumper->dump($data);
        $output = ob_get_clean();
        $collector->serialize();
    }

    public function testCollectDefault()
    {
        $data = new Data(array(array(123)));

        $request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $collector = new DumpDataCollector($requestStack);
        $collector->setToken('DumpDataCollectorTest');
        $response = new Response();
        $request->setRequestFormat('html');
        $response->setContent('<html><body></body></html>');
        $collector->onKernelResponse(
            new FilterResponseEvent(
                $this->getKernel(), $request, HttpKernelInterface::MASTER_REQUEST, $response
            )
        );
        $collector->dump($data);
        $line = __LINE__ - 1;

        /** @var DumpData $data */
        $data = $collector->collect();
        $this->assertInstanceof('Symfony\Component\VarDumper\Profiler\DumpData', $data);
        $this->assertEquals(1, $data->getDumpsCount());
        $dumps = $data->getDumps('html');
        $this->assertCount(1, $dumps);
        $this->assertEquals($line, $dumps[0]['line']);
        $this->assertEquals(__FILE__, $dumps[0]['file']);
    }

    public function testCollectRedirectResponse()
    {
        $data = new Data(array(array(123)));

        $request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $collector = new DumpDataCollector($requestStack);
        $collector->setToken('DumpDataCollectorTest');
        $collector->onKernelResponse(
            new FilterResponseEvent(
                $this->getKernel(), $request, HttpKernelInterface::MASTER_REQUEST, new RedirectResponse('dummy')
            )
        );
        $collector->dump($data);
        $line = __LINE__ - 1;

        ob_start();
        $collector->collect();
        $output = ob_get_clean();

        if (PHP_VERSION_ID >= 50400) {
            $this->assertSame("DumpDataCollectorTest.php on line {$line}:\n123\n", $output);
        } else {
            $this->assertSame("\"DumpDataCollectorTest.php on line {$line}:\"\n123\n", $output);
        }
        $this->assertSame(1, $collector->getDumpsCount());
        $collector->serialize();
    }

    public function testCollectWithDumper()
    {
        $request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $dumper = $this->getMockBuilder('Symfony\Component\VarDumper\Dumper\DataDumperInterface')->getMock();
        $collector = new DumpDataCollector($requestStack, null, null, null, $dumper);
        $collector->setToken('DumpDataCollectorTest');
        $collector->onKernelResponse(
            new FilterResponseEvent(
                $this->getKernel(), $request, HttpKernelInterface::MASTER_REQUEST, new RedirectResponse('dummy')
            )
        );

        $this->assertNULL($collector->collect());
        $collector2 = clone $collector;
        $collector2->unserialize($collector->serialize());
    }

    public function testCollectWithoutRequestOrResponse()
    {
        $requestStack = new RequestStack();
        $collector = new DumpDataCollector($requestStack);
        $this->assertNULL($collector->collect());
        $requestStack->push(new Request());
        $this->assertNULL($collector->collect());
    }

    public function testCollectHtml()
    {
        $data = new Data(array(array(123)));

        $requestStack = new RequestStack();
        $collector = new DumpDataCollector($requestStack, null, 'test://%f:%l', null);

        $collector->dump($data);
        $line = __LINE__ - 1;
        $file = __FILE__;
        if (PHP_VERSION_ID >= 50400) {
            $xOutput = <<<EOTXT
 <pre class=sf-dump id=sf-dump data-indent-pad="  "><a href="test://{$file}:{$line}" title="{$file}"><span class=sf-dump-meta>DumpDataCollectorTest.php</span></a> on line <span class=sf-dump-meta>{$line}</span>:
<span class=sf-dump-num>123</span>
</pre>

EOTXT;
        } else {
            $len = strlen("DumpDataCollectorTest.php on line {$line}:");
            $xOutput = <<<EOTXT
 <pre class=sf-dump id=sf-dump data-indent-pad="  ">"<span class=sf-dump-str title="{$len} characters">DumpDataCollectorTest.php on line {$line}:</span>"
</pre>
<pre class=sf-dump id=sf-dump data-indent-pad="  "><span class=sf-dump-num>123</span>
</pre>

EOTXT;
        }

        ob_start();
        $response = new Response();
        $response->headers->set('Content-Type', 'text/html');
        $requestStack->push(new Request());

        $collector->onKernelResponse(
            new FilterResponseEvent(
                $this->getKernel(), $requestStack->getMasterRequest(), HttpKernelInterface::MASTER_REQUEST, $response
            )
        );
        $collector->collect();
        $output = ob_get_clean();
        $output = preg_replace('#<(script|style).*?</\1>#s', '', $output);
        $output = preg_replace('/sf-dump-\d+/', 'sf-dump', $output);

        $this->assertSame($xOutput, $output);
        $this->assertSame(1, $collector->getDumpsCount());
        $collector->serialize();
    }

    public function testFlush()
    {
        $data = new Data(array(array(456)));
        $collector = new DumpDataCollector(new RequestStack());
        $collector->dump($data);
        $line = __LINE__ - 1;

        ob_start();
        $collector = null;
        if (PHP_VERSION_ID >= 50400) {
            $this->assertSame("DumpDataCollectorTest.php on line {$line}:\n456\n", ob_get_clean());
        } else {
            $this->assertSame("\"DumpDataCollectorTest.php on line {$line}:\"\n456\n", ob_get_clean());
        }
    }

    public function testDumpFromTwigTemplate()
    {
        $collector = new DumpDataCollector(new RequestStack(), new Stopwatch());
        $dumper = new VarDumper();
        $dumper->setHandler(array($collector, 'dump'));
        $environment = $this->getMockBuilder('Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $loader = $this->getMockBuilder('Twig_LoaderInterface')->getMock();
        $environment->expects($this->any())->method('getLoader')->willReturn($loader);
        $src = '';
        for ($i = 1; $i < 100; $i++) {
            $src .= $i."\n";
        }
        $loader->expects($this->any())->method('getSource')->willReturn($src);
        $template = new TwigTemplate($environment, $dumper);
        ob_start();
        $template->display(array(123));
        $output = ob_get_clean();
        $collector->serialize();
    }

    public function testDumpCallUserFunction()
    {
        $collector = new DumpDataCollector(new RequestStack());
        ob_start();
        call_user_func(array($this, 'dump'), new Data(array(array(123))), $collector);
        $output = ob_get_clean();
        $collector->serialize();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid dump format: json
     */
    public function testDumpsUnsupportedFormat()
    {
        $collector = new DumpDataCollector(new RequestStack());
        $collector->getDumps('json');
    }

    public function testSubscribedEvents()
    {
        $events = DumpDataCollector::getSubscribedEvents();
        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
    }

    protected function getKernel()
    {
        return $this->getMock('Symfony\Component\HttpKernel\KernelInterface');
    }

    private function dump(Data $data, $dumper)
    {
        $dumper->dump($data);
    }
}
