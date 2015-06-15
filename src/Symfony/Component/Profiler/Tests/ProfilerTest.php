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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\DataCollector\TimeDataCollector as DeprecatedTimeDataCollector;
use Symfony\Component\HttpKernel\DataCollector\RequestDataCollector as DeprecatedRequestDataCollector;
use Symfony\Component\HttpKernel\DataCollector\MemoryDataCollector as DeprecatedMemoryDataCollector;
use Symfony\Component\Profiler\DataCollector\MemoryDataCollector;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Profiler\Encoder\ConsoleProfileEncoder;
use Symfony\Component\Profiler\Encoder\HttpProfileEncoder;
use Symfony\Component\Profiler\ProfileInterface;
use Symfony\Component\Profiler\Storage\ProfilerStorageInterface;
use Symfony\Component\Profiler\Storage\SqliteProfilerStorage;
use Symfony\Component\Profiler\Profiler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\RequestDataCollector;
use Symfony\Component\Profiler\Tests\Mock\MockEncoder;
use Symfony\Component\Profiler\Tests\Mock\MockProfile;

class ProfilerTest extends \PHPUnit_Framework_TestCase
{
    private $tmp;
    /** @var SqliteProfilerStorage */
    private $storage;

    /** @var Profiler */
    private $profiler;
    private $profile;

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Collector "memory" does not exist.
     */
    public function testDataCollectors()
    {
        $requestCollector = new RequestDataCollector(new RequestStack());

        $this->profiler->set(array($requestCollector));

        $this->assertTrue($this->profiler->has('request'));
        $this->assertCount(1, $this->profiler->all());
        $this->assertEquals($requestCollector, $this->profiler->get('request'));

        $this->profiler->get('memory');
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
        $this->profiler->add($collector);
        $this->profiler->add(new MemoryDataCollector());

        $this->assertFalse($this->profile->has('request'));

        $this->profiler->profile($this->profile);

        $this->assertTrue($this->profile->has('request'));
        $this->assertEquals(array('foo' => 'bar'), $this->profile->get('request')->getRequestQuery()->all());

        $this->assertTrue($this->profiler->save($this->profile));
    }

    public function testProfileRequest()
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
        $this->profiler->add($collector);
        $this->profiler->add(new MemoryDataCollector());

        $this->profiler->disable();
        $this->assertNull($this->profiler->profileRequest($request, $response));

        $this->profiler->enable();
        $profile = $this->profiler->profileRequest($request, $response);

        $this->assertTrue($profile->has('request'));
        $this->assertEquals(array('foo' => 'bar'), $profile->get('request')->getRequestQuery()->all());

        $this->assertTrue($this->profiler->save($profile));
    }

    public function testProfileCommand()
    {
        $command = new Command('test');
        $input = new ArgvInput();

        $this->profiler->add(new MemoryDataCollector());

        $this->profiler->disable();
        $this->assertNull($this->profiler->profileCommand($command, $input, 1));

        $this->profiler->enable();
        $profile = $this->profiler->profileCommand($command, $input, 1);

        $this->assertTrue($profile->has('memory'));

        $this->assertTrue($this->profiler->save($profile));
        $this->assertInstanceof('Symfony\Component\Profiler\ConsoleProfile', $this->profiler->load($profile->getToken()));
    }

    public function testFindWorksWithDates()
    {
        $this->assertCount(0, $this->profiler->findBy(array(), null, '7th April 2014', '9th April 2014'));
        $this->assertCount(0, $this->profiler->findBy(array(), null, '1396828800', '1397001600'));
        $this->assertCount(0, $this->profiler->findBy(array(), null, 'some string', ''));
    }

    public function testLoadFromResponse()
    {
        $response = new Response('', 204);

        $this->assertFalse($this->profiler->loadFromResponse($response));
        $response->headers->set('X-Debug-Token', 'tokens');
        $this->assertNULL($this->profiler->loadFromResponse($response));
    }

    public function testPurge()
    {
        $storage = new DummyStorage();
        $profiler = new Profiler($storage);

        $storage->write($this->profile);
        $this->assertCount(1, $storage->findBy());
        $profiler->purge();
        $this->assertCount(0, $storage->findBy());
    }

    public function testExport()
    {
        $profiler = new Profiler(new DummyStorage());

        $this->assertEquals(base64_encode(serialize($this->profile)), $profiler->export($this->profile));
    }

    public function testImport()
    {
        $storage = new DummyStorage();
        $profiler = new Profiler($storage);

        $base64Profile = base64_encode(serialize($this->profile));

        $this->assertInstanceof('Symfony\Component\Profiler\Tests\Mock\MockProfile', $profiler->import($base64Profile));
        $this->assertFalse($profiler->import($base64Profile));
    }

    public function testSave()
    {
        $logger = $this->getMockBuilder('Psr\Log\LoggerInterface')->getMock();

        $logger->expects($this->once())->method('warning');

        $storage = new DummyStorage();
        $profiler = new Profiler($storage, $logger);

        $this->assertTrue($profiler->save($this->profile));
        $this->assertFalse($profiler->save($this->profile));
    }

    public function testDeprecatedCollectors()
    {
        //$this->markTestSkipped('Test for deprecated DataCollectors.');

        $request = new Request();
        $request->query->set('foo', 'bar');
        $response = new Response('', 204);

        $this->profiler->add(new DeprecatedRequestDataCollector());
        $this->profiler->add(new DeprecatedMemoryDataCollector());
        $this->profiler->add(new DeprecatedTimeDataCollector());

        $this->profiler->enable();
        $profile = $this->profiler->profileRequest($request, $response);

        $this->assertTrue($profile->has('request'));
        $this->assertEquals(array('foo' => 'bar'), $profile->get('request')->getRequestQuery()->all());
        $this->assertTrue($profile->has('time'));
        $this->assertTrue($profile->has('memory'));
        $this->assertTrue($this->profiler->save($profile));
        $this->assertInstanceof('Symfony\Component\Profiler\HttpProfile', $this->profiler->loadFromResponse($response));

        $this->assertCount(0, $this->profiler->find('', '', 10, '', '7th April 2014', '9th April 2014'));
        $this->assertCount(0, $this->profiler->find('127.0.0.1', 'http://foo.bar/', 10, 'GET', '7th April 2014', '9th April 2014'));
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

        $this->storage = new SqliteProfilerStorage('sqlite:' . $this->tmp);
        $this->storage->addEncoder(new MockEncoder());
        $this->storage->addEncoder(new HttpProfileEncoder());
        $this->storage->addEncoder(new ConsoleProfileEncoder());
        $this->storage->purge();

        $this->profile = new MockProfile('Mock');
        $this->profiler = new Profiler($this->storage);
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
