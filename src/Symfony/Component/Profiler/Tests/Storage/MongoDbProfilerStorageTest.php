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

use Symfony\Component\Profiler\DataCollector\RuntimeDataCollectorInterface;
use Symfony\Component\Profiler\ProfileData\ProfileDataInterface;
use Symfony\Component\Profiler\Storage\MongoDbProfilerStorage;
use Symfony\Component\Profiler\Profile;
use Symfony\Component\Profiler\DataCollector\AbstractDataCollector;

class DummyMongoDbProfilerStorage extends MongoDbProfilerStorage
{
    public function getMongo()
    {
        return parent::getMongo();
    }
}

class MongoDbProfilerStorageTestDataCollector extends AbstractDataCollector implements RuntimeDataCollectorInterface
{
    public function __construct(MongoDbProfilerStorageTestProfileData $data)
    {
        $this->data = $data;
    }

    public function collect()
    {
        return $this->data;
    }

    public function getName()
    {
        return 'test_data_collector';
    }
}

class MongoDbProfilerStorageTestProfileData implements ProfileDataInterface
{
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getName()
    {
        return 'test_data_collector';
    }
}

class MongoDbProfilerStorageTest extends AbstractProfilerStorageTest
{
    protected static $storage;

    public static function setUpBeforeClass()
    {
        if (extension_loaded('mongo')) {
            self::$storage = new DummyMongoDbProfilerStorage('mongodb://localhost/symfony_tests/profiler_data', '', '', 86400);
            try {
                self::$storage->getMongo();
            } catch (\MongoConnectionException $e) {
                self::$storage = null;
            }
        }
    }

    public static function tearDownAfterClass()
    {
        if (self::$storage) {
            self::$storage->purge();
            self::$storage = null;
        }
    }

    public function getDsns()
    {
        return array(
            array('mongodb://localhost/symfony_tests/profiler_data', array(
                'mongodb://localhost/symfony_tests',
                'symfony_tests',
                'profiler_data',
            )),
            array('mongodb://user:password@localhost/symfony_tests/profiler_data', array(
                'mongodb://user:password@localhost/symfony_tests',
                'symfony_tests',
                'profiler_data',
            )),
            array('mongodb://user:password@localhost/admin/symfony_tests/profiler_data', array(
                'mongodb://user:password@localhost/admin',
                'symfony_tests',
                'profiler_data',
            )),
            array('mongodb://user:password@localhost:27009,localhost:27010/?replicaSet=rs-name&authSource=admin/symfony_tests/profiler_data', array(
                'mongodb://user:password@localhost:27009,localhost:27010/?replicaSet=rs-name&authSource=admin',
                'symfony_tests',
                'profiler_data',
            )),
        );
    }

    public function testCleanup()
    {
        $dt = new \DateTime('-2 day');
        for ($i = 0; $i < 3; ++$i) {
            $dt->modify('-1 day');
            $profile = new Profile('time_'.$i);
            $profile->setTime($dt->getTimestamp());
            $profile->setMethod('GET');
            self::$storage->write($profile);
        }
        $records = self::$storage->find('', '', 3, 'GET');
        $this->assertCount(1, $records, '->find() returns only one record');
        $this->assertEquals($records[0]['token'], 'time_2', '->find() returns the latest added record');
        self::$storage->purge();
    }

    /**
     * @dataProvider getDsns
     */
    public function testDsnParser($dsn, $expected)
    {
        $m = new \ReflectionMethod(self::$storage, 'parseDsn');
        $m->setAccessible(true);

        $this->assertEquals($expected, $m->invoke(self::$storage, $dsn));
    }

    public function testUtf8()
    {
        $profile = new Profile('utf8_test_profile');

        $utf8Data = 'HЁʃʃϿ, ϢorЃd!';

        $collector = new MongoDbProfilerStorageTestDataCollector(
            new MongoDbProfilerStorageTestProfileData($utf8Data)
        );

        $profile->addProfileData($collector->getName(), $collector->collect());

        self::$storage->write($profile);

        $readProfile = self::$storage->read('utf8_test_profile');
        $data = $readProfile->getData();

        $this->assertCount(1, $data);
        $this->assertArrayHasKey('test_data_collector', $data);
        $this->assertEquals($utf8Data, $data['test_data_collector']->getData(), 'Non-UTF8 data is properly encoded/decoded');
    }

    /**
     * @return \Symfony\Component\Profiler\ProfilerStorageInterface
     */
    protected function getStorage()
    {
        return self::$storage;
    }

    protected function setUp()
    {
        if (self::$storage) {
            self::$storage->purge();
        } else {
            $this->markTestSkipped('MongoDbProfilerStorageTest requires the mongo PHP extension and a MongoDB server on localhost');
        }
    }
}
