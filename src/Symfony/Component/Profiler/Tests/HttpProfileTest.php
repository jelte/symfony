<?php

namespace Symfony\Component\Profiler\Tests;

use Symfony\Component\Profiler\DataCollector\MemoryDataCollector;
use Symfony\Component\Profiler\HttpProfile;

class HttpProfileTest extends \PHPUnit_Framework_TestCase
{
    public function testHoldsProfileData()
    {
        $profile = new HttpProfile('test', '127.0.0.1', '/', 'GET', 200);

        $collector = new MemoryDataCollector();
        $profile->add('memory', $collector->lateCollect());
        $this->assertTrue($profile->has('memory'));
        $this->assertInstanceof('Symfony\Component\Profiler\ProfileData\ProfileDataInterface', $profile->get('memory'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage ProfileData "memory" does not exist.
     */
    public function testProfileDataCollector()
    {
        $profile = new HttpProfile('test', '127.0.0.1', '/', 'GET', 200);

        $this->assertFalse($profile->has('memory'));
        $profile->get('memory');
    }
}
