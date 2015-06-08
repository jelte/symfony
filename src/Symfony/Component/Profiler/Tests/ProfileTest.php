<?php


namespace Symfony\Component\Profiler\Tests;


use Symfony\Component\Profiler\DataCollector\MemoryDataCollector;
use Symfony\Component\Profiler\Profile;


class ProfileTest extends \PHPUnit_Framework_TestCase
{

    public function testMutableToken()
    {
        $profile = new Profile('test');
        $profile->setToken('changed');
        $this->assertEquals('changed', $profile->getToken());
    }

    public function testHoldsCollectors()
    {
        $profile = new Profile('test');

        $collector = new MemoryDataCollector();
        $profile->setCollectors(array($collector));
        $this->assertTrue($profile->hasCollector('memory'));
        $this->assertEquals($collector, $profile->getCollector('memory'));
        $this->assertCount(1, $profile->getCollectors());
        $this->assertEquals($collector, $profile->get('memory'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Collector "memory" does not exist.
     */
    public function testUnknownCollector()
    {
        $profile = new Profile('test');

        $profile->getCollector('memory');
    }

    public function testHoldsProfileData()
    {
        $profile = new Profile('test');

        $collector = new MemoryDataCollector();
        $profile->addProfileData('memory', $collector->lateCollect());
        $this->assertTrue($profile->hasProfileData('memory'));
        $this->assertInstanceof('Symfony\Component\Profiler\ProfileData\ProfileDataInterface', $profile->getProfileData('memory'));
        $this->assertInstanceof('Symfony\Component\Profiler\ProfileData\ProfileDataInterface', $profile->get('memory'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage ProfileData "memory" does not exist.
     */
    public function testProfileDataCollector()
    {
        $profile = new Profile('test');

        $this->assertFalse($profile->hasProfileData('memory'));
        $profile->getProfileData('memory');
    }
}