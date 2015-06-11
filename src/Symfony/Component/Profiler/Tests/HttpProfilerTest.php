<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Profiler\Tests;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\DataCollector\RouterDataCollector;
use Symfony\Component\HttpKernel\DataCollector\TimeDataCollector;
use Symfony\Component\Profiler\DataCollector\MemoryDataCollector;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Profiler\Encoder\HttpProfileEncoder;
use Symfony\Component\Profiler\HttpProfile;
use Symfony\Component\Profiler\ProfileInterface;
use Symfony\Component\Profiler\Storage\ProfilerStorageInterface;
use Symfony\Component\Profiler\Storage\SqliteProfilerStorage;
use Symfony\Component\Profiler\HttpProfiler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\RequestDataCollector;

class HttpProfilerTest extends \PHPUnit_Framework_TestCase
{
    private $tmp;
    /** @var SqliteProfilerStorage */
    private $storage;

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Collector "memory" does not exist.
     */
    public function testDataCollectors()
    {
        $requestStack = new RequestStack();
        $profiler = new HttpProfiler($requestStack, $this->storage);
        $requestCollector = new RequestDataCollector($requestStack);

        $profiler->set(array($requestCollector));

        $this->assertTrue($profiler->has('request'));

        $this->assertCount(1, $profiler->all());

        $this->assertEquals($requestCollector, $profiler->get('request'));

        $profiler->get('memory');
    }

    public function testCollect()
    {
        $requestStack = new RequestStack();
        $request = new Request();
        $request->query->set('foo', 'bar');
        $requestStack->push($request);
        $response = new Response('', 204);
        $collector = new RequestDataCollector($requestStack);
        $collector->onKernelResponse(
            new FilterResponseEvent(
                $this->getMock('Symfony\Component\HttpKernel\KernelInterface'),
                $requestStack->getMasterRequest(),
                HttpKernelInterface::MASTER_REQUEST,
                $response
            )
        );
        $profiler = new HttpProfiler($requestStack, $this->storage);
        $profiler->add($collector);
        $profiler->add(new MemoryDataCollector());

        $this->assertNULL($profiler->profile());
        $profiler->addResponse($request, $response);

        $profiler->disable();
        $this->assertNULL($profiler->profile());

        $profiler->enable();
        $profile = $profiler->profile();

        $this->assertSame(204, $profile->getStatusCode());
        $this->assertSame('GET', $profile->getMethod());
        $this->assertEquals(array('foo' => 'bar'), $profile->get('request')->getRequestQuery()->all());

        $this->assertTrue($profiler->save($profiler->profile()));
    }

    public function testCollectWithoutRequest()
    {
        $requestStack = new RequestStack();
        $collector = new RequestDataCollector($requestStack);

        $profiler = new HttpProfiler($requestStack, $this->storage);
        $profiler->add($collector);

        $profile = $profiler->profile();

        $this->assertNULL($profile);
    }

    public function testFindWorksWithDates()
    {
        $profiler = new HttpProfiler(new RequestStack(), $this->storage);

        $this->assertCount(0, $profiler->findBy(array(), null, '7th April 2014', '9th April 2014'));
    }

    public function testFindWorksWithTimestamps()
    {
        $profiler = new HttpProfiler(new RequestStack(), $this->storage);

        $this->assertCount(0, $profiler->findBy(array(), null, '1396828800', '1397001600'));
    }

    public function testFindWorksWithInvalidDates()
    {
        $profiler = new HttpProfiler(new RequestStack(), $this->storage);

        $this->assertCount(0, $profiler->findBy(array(), null, 'some string', ''));
    }

    public function testLoadFromResponse()
    {
        $response = new Response('', 204);

        $profiler = new HttpProfiler(new RequestStack(), $this->storage);

        $this->assertFalse($profiler->loadFromResponse($response));
        $response->headers->set('X-Debug-Token', 'tokens');
        $this->assertNULL($profiler->loadFromResponse($response));
    }

    public function testPurge()
    {
        $storage = new DummyStorage();
        $profiler = new HttpProfiler(new RequestStack(), $storage);

        $storage->write(new HttpProfile('test', '127.0.0.1', '/', 'GET', 200));
        $this->assertCount(1, $storage->findBy());
        $profiler->purge();
        $this->assertCount(0, $storage->findBy());
    }

    public function testExport()
    {
        $storage = new DummyStorage();
        $profiler = new HttpProfiler(new RequestStack(), $storage);

        $profile = new HttpProfile('test', '127.0.0.1', '/', 'GET', 200);

        $this->assertEquals(base64_encode(serialize($profile)), $profiler->export($profile));
    }

    public function testImport()
    {
        $storage = new DummyStorage();
        $profiler = new HttpProfiler(new RequestStack(), $storage);

        $profile = new HttpProfile('test', '127.0.0.1', '/', 'GET', 200);
        $base64Profile = base64_encode(serialize($profile));

        $this->assertInstanceof('Symfony\Component\Profiler\HttpProfile', $profiler->import($base64Profile));
        $this->assertFalse($profiler->import($base64Profile));
    }

    public function testSave()
    {
        $logger = $this->getMockBuilder('Psr\Log\LoggerInterface')->getMock();

        $logger->expects($this->once())->method('warning');

        $storage = new DummyStorage();
        $profiler = new HttpProfiler(new RequestStack(), $storage, $logger);

        $profile = new HttpProfile('test', '127.0.0.1', '/', 'GET', 200);

        $this->assertTrue($profiler->save($profile));
        $this->assertFalse($profiler->save($profile));
    }

    public function testDeprecatedCollectors()
    {
        $this->markTestSkipped('Test for deprecated DataCollectors.');

        $requestStack = new RequestStack();
        $request = new Request();
        $requestStack->push($request);

        $profiler = new HttpProfiler($requestStack, $this->storage);

        $profiler->addResponse($request, new Response());
        $timeCollector = new TimeDataCollector();

        $profiler->set(array($timeCollector));

        $this->assertTrue($profiler->has('time'));

        $this->assertCount(1, $profiler->all());

        $this->assertEquals($timeCollector, $profiler->get('time'));

        $profile = $profiler->profile();

        $this->assertTrue($profile->has('time'));
        $this->assertEquals($timeCollector, $profile->get('time'));
    }

    protected function setUp()
    {
        if (!class_exists('SQLite3') && (!class_exists('PDO') || !in_array('sqlite', \PDO::getAvailableDrivers()))) {
            $this->markTestSkipped('This test requires SQLite support in your environment');
        }

        $this->tmp = tempnam(sys_get_temp_dir(), 'sf2_profiler');
        if (file_exists($this->tmp)) {
            @unlink($this->tmp);
        }

        $this->storage = new SqliteProfilerStorage('sqlite:'.$this->tmp);
        $this->storage->addEncoder(new HttpProfileEncoder());
        $this->storage->purge();
    }

    protected function tearDown()
    {
        if (null !== $this->storage) {
            $this->storage->purge();
            $this->storage = null;

            @unlink($this->tmp);
        }
    }
}

class DummyStorage implements ProfilerStorageInterface
{
    protected $profiles = array();

    public function findBy(array $criteria = array(), $limit = null, $start = null, $end = null)
    {
        return $this->profiles;
    }

    public function read($token)
    {
        if (!isset($this->profiles[$token])) {
            return false;
        }

        return $this->profiles[$token];
    }

    public function write(ProfileInterface $profile)
    {
        if (isset($this->profiles[$profile->getToken()])) {
            return false;
        }
        $this->profiles[$profile->getToken()] = $profile;

        return true;
    }

    public function purge()
    {
        $this->profiles = array();
    }
}
