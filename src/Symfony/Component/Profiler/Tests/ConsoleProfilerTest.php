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

use Symfony\Component\Profiler\ConsoleProfile;
use Symfony\Component\Profiler\ConsoleProfiler;
use Symfony\Component\Profiler\DataCollector\ConfigDataCollector;
use Symfony\Component\Profiler\DataCollector\MemoryDataCollector;
use Symfony\Component\Profiler\Encoder\ConsoleProfileEncoder;
use Symfony\Component\Profiler\Storage\SqliteProfilerStorage;
use Symfony\Component\Console\Command\Command;

class ConsoleProfilerTest extends \PHPUnit_Framework_TestCase
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
        $profiler = new ConsoleProfiler($this->storage);
        $configCollector = new ConfigDataCollector();

        $profiler->set(array($configCollector));

        $this->assertTrue($profiler->has('config'));

        $this->assertCount(1, $profiler->all());

        $this->assertEquals($configCollector, $profiler->get('config'));

        $profiler->get('memory');
    }

    public function testCollect()
    {
        $collector = new ConfigDataCollector();
        $profiler = new ConsoleProfiler($this->storage);
        $profiler->add($collector);
        $profiler->add(new MemoryDataCollector());

        $this->assertNULL($profiler->profile());
        $command = $this->getMockBuilder('Symfony\Component\Console\Command\Command')
            ->disableOriginalConstructor()
            ->getMock();

        $command->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('dummy'));

        $input = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')->getMock();

        $input->expects($this->once())
            ->method('getArguments')
            ->will($this->returnValue(array()));

        $input->expects($this->once())
            ->method('getOptions')
            ->will($this->returnValue(array()));

        $profiler->addCommand($command, $input, 5);

        $profiler->disable();
        $this->assertNULL($profiler->profile());

        $profiler->enable();
        $profile = $profiler->profile();

        $this->assertInstanceof('Symfony\Component\Profiler\ConsoleProfile', $profile);
        $this->assertSame(5, $profile->getExitCode());
        $this->assertSame('dummy', $profile->getCommand());
        $this->assertCount(0, $profile->getArguments());
        $this->assertCount(0, $profile->getOptions());

        $this->assertTrue($profiler->save($profile));
    }

    public function testFindCommand()
    {
        $profiler = new ConsoleProfiler($this->storage);

        $profile = new ConsoleProfile('test', 'dummy', array(), array(), 1);
        $profiler->save($profile);

        $result = $profiler->findBy(array('command' => 'dummy'), 10, null, null);
        $this->assertCount(1, $result);
    }

    public function testFindWorksWithDates()
    {
        $profiler = new ConsoleProfiler($this->storage);

        $this->assertCount(0, $profiler->findBy(array(), null, '7th April 2014', '9th April 2014'));
    }

    public function testFindWorksWithTimestamps()
    {
        $profiler = new ConsoleProfiler($this->storage);

        $this->assertCount(0, $profiler->findBy(array(), null, '1396828800', '1397001600'));
    }

    public function testFindWorksWithInvalidDates()
    {
        $profiler = new ConsoleProfiler($this->storage);

        $this->assertCount(0, $profiler->findBy(array(), null, 'some string', ''));
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
        $this->storage->addEncoder(new ConsoleProfileEncoder());
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
