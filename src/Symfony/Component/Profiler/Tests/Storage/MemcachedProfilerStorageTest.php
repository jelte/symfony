<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Profiler\Tests\Storage;

use Symfony\Component\Profiler\Encoder\ConsoleProfileEncoder;
use Symfony\Component\Profiler\Encoder\HttpProfileEncoder;
use Symfony\Component\Profiler\Storage\MemcachedProfilerStorage;
use Symfony\Component\Profiler\Tests\Storage\Mock\MemcachedMock;

class MemcachedProfilerStorageTest extends AbstractProfilerStorageTest
{
    protected static $storage;

    protected function setUp()
    {
        $memcachedMock = new MemcachedMock();
        $memcachedMock->addServer('127.0.0.1', 11211);

        self::$storage = new MemcachedProfilerStorage('memcached://127.0.0.1:11211', '', '', 86400);
        self::$storage->addEncoder(new HttpProfileEncoder());
        self::$storage->addEncoder(new ConsoleProfileEncoder());
        self::$storage->setMemcached($memcachedMock);

        if (self::$storage) {
            self::$storage->purge();
        }
    }

    protected function tearDown()
    {
        if (self::$storage) {
            self::$storage->purge();
            self::$storage = false;
        }
    }

    /**
     * @return \Symfony\Component\Profiler\ProfilerStorageInterface
     */
    protected function getStorage()
    {
        return self::$storage;
    }

    /**
     * @expectedException RuntimeException
     */
    public function testWithInvalidDNS()
    {
        $storage = new MemcachedProfilerStorage('memcache://127.0.0.1:11211', '', '', 86400);
        $storage->read('test');
    }

    /**
     * @requires extension memcached
     */
    public function testWithDNS()
    {
        $storage = new MemcachedProfilerStorage('memcached://127.0.0.1:11211', '', '', 86400);
        $storage->read('test');
    }
}
