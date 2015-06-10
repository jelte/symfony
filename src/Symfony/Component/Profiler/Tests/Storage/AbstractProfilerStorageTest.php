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

use Symfony\Component\Profiler\ConsoleProfile;
use Symfony\Component\Profiler\HttpProfile;

abstract class AbstractProfilerStorageTest extends \PHPUnit_Framework_TestCase
{
    public function testStoreHttpProfile()
    {
        for ($i = 0; $i < 10; ++$i) {
            $profile = new HttpProfile('token_'.$i, '127.0.0.1', 'http://foo.bar', 'GET', 200);
            $this->getStorage()->write($profile);
        }
        $this->assertCount(10, $this->getStorage()->findBy(array('ip' => '127.0.0.1', 'url' => 'http://foo.bar', 'method' => 'GET'), 20), sprintf('->write() stores data in the storage "%s"', get_class($this->getStorage())));
    }

    public function testStoreConsoleProfile()
    {
        for ($i = 0; $i < 10; ++$i) {
            $profile = new ConsoleProfile('token_'.$i, 'debug:test', array($i), array(), 1);
            $this->getStorage()->write($profile);
        }
        $this->assertCount(10, $this->getStorage()->findBy(array('command' => 'debug:test'), 10), sprintf('->write() stores data in the storage "%s"', get_class($this->getStorage())));
    }

    public function testChildren()
    {
        $parentProfile = new HttpProfile('token_parent', '127.0.0.1', 'http://foo.bar/parent', 'GET', 200);
        $childProfile = new HttpProfile('token_child', '127.0.0.1', 'http://foo.bar/child', 'GET', 200);

        $parentProfile->addChild($childProfile);

        $this->getStorage()->write($parentProfile);

        // Load them from storage
        $parentProfile = $this->getStorage()->read('token_parent');
        /** @var HttpProfile $childProfile */
        $childProfile = $this->getStorage()->read('token_child');

        // Check if childProfile is loaded
        $this->assertNotNull($childProfile);

        // Check child has link to parent
        $this->assertNotNull($childProfile->getParentToken());
        $this->assertEquals($parentProfile->getToken(), $childProfile->getParentToken());

        // Check parent has child
        $children = $parentProfile->getChildren();
        $this->assertCount(1, $children);
        $this->assertEquals($childProfile->getToken(), $children[0]->getToken());
    }

    public function testStoreSpecialCharsInUrl()
    {
        // The storage accepts special characters in URLs (Even though URLs are not
        // supposed to contain them)
        $profile = new HttpProfile('simple_quote', '127.0.0.1', 'http://foo.bar/\'', 'GET', 200);
        $this->getStorage()->write($profile);
        $this->assertTrue(false !== $this->getStorage()->read('simple_quote'), '->write() accepts single quotes in URL');

        $profile = new HttpProfile('simple_quote', '127.0.0.1', 'http://foo.bar/\"', 'GET', 200);
        $this->getStorage()->write($profile);
        $this->assertTrue(false !== $this->getStorage()->read('double_quote'), '->write() accepts double quotes in URL');

        $profile = new HttpProfile('backslash', '127.0.0.1', 'http://foo.bar/\\', 'GET', 200);
        $this->getStorage()->write($profile);
        $this->assertTrue(false !== $this->getStorage()->read('backslash'), '->write() accepts backslash in URL');

        $profile = new HttpProfile('comma', '127.0.0.1', 'http://foo.bar/,', 'GET', 200);
        $this->getStorage()->write($profile);
        $this->assertTrue(false !== $this->getStorage()->read('comma'), '->write() accepts comma in URL');
    }

    public function testStoreDuplicateToken()
    {
        $profile = new HttpProfile('token', '127.0.0.1', 'http://example.com/', 'GET', 200);
        $this->assertTrue($this->getStorage()->write($profile), '->write() returns true when the token is unique');

        $profile = new HttpProfile('token', '127.0.0.1', 'http://example.net/', 'GET', 200);
        $this->assertTrue($this->getStorage()->write($profile), '->write() returns true when the token is already present in the storage');
        $this->assertEquals('http://example.net/', $this->getStorage()->read('token')->getUrl(), '->write() overwrites the current profile data');

        $this->assertCount(1, $this->getStorage()->findBy(array(), 1000), '->findBy() does not return the same profile twice');
    }

    public function testRetrieveByIp()
    {
        $profile = new HttpProfile('token', '127.0.0.1', 'http://example.net/', 'GET', 200);
        $this->getStorage()->write($profile);

        $this->assertCount(1, $this->getStorage()->findBy(array('ip' => '127.0.0.1'), 10), '->findBy() retrieve a record by IP');
        $this->assertCount(0, $this->getStorage()->findBy(array('ip' => '127.0.%.1'), 10), '->findBy() does not interpret a "%" as a wildcard in the IP');
        $this->assertCount(0, $this->getStorage()->findBy(array('ip' => '127.0._.1'), 10), '->findBy() does not interpret a "_" as a wildcard in the IP');
    }

    public function testRetrieveByUrl()
    {
        $profile = new HttpProfile('simple_quote', '127.0.0.1', 'http://foo.bar/\'', 'GET', 200);
        $this->getStorage()->write($profile);
        $this->assertCount(1, $this->getStorage()->findBy(array('url' => 'http://foo.bar/\''), 10), '->findBy() accepts single quotes in URLs');

        $profile = new HttpProfile('double_quote', '127.0.0.1', 'http://foo.bar/"', 'GET', 200);
        $this->getStorage()->write($profile);
        $this->assertCount(1, $this->getStorage()->findBy(array('url' => 'http://foo.bar/"'), 10), '->findBy() accepts double quotes in URLs');

        $profile = new HttpProfile('backslash', '127.0.0.1', 'http://foo\\bar/', 'GET', 200);
        $this->getStorage()->write($profile);
        $this->assertCount(1, $this->getStorage()->findBy(array('url' => 'http://foo\\bar/'), 10), '->findBy() accepts backslash in URLs');

        $profile = new HttpProfile('percent', '127.0.0.1', 'http://foo.bar/%', 'GET', 200);
        $this->getStorage()->write($profile);
        $this->assertCount(1, $this->getStorage()->findBy(array('url' => 'http://foo.bar/%'), 10), '->findBy() does not interpret a "%" as a wildcard in the URL');

        $profile = new HttpProfile('underscore', '127.0.0.1', 'http://foo.bar/_', 'GET', 200);
        $this->getStorage()->write($profile);
        $this->assertCount(1, $this->getStorage()->findBy(array('url' => 'http://foo.bar/_'), 10), '->findBy() does not interpret a "_" as a wildcard in the URL');

        $profile = new HttpProfile('semicolon', '127.0.0.1', 'http://foo.bar/;', 'GET', 200);
        $this->getStorage()->write($profile);
        $this->assertCount(1, $this->getStorage()->findBy(array('url' => 'http://foo.bar/;'), 10), '->findBy() accepts semicolon in URLs');
    }

    public function testStoreTime()
    {
        $dt = new \DateTime('now');
        $start = $dt->getTimestamp();

        for ($i = 0; $i < 3; ++$i) {
            $dt->modify('+1 minute');
            $profile = new HttpProfile('time_'.$i, '127.0.0.1', 'http://foo.bar', 'GET', 200, $dt->getTimestamp());
            $this->getStorage()->write($profile);
        }

        $records = $this->getStorage()->findBy(array(), 3, $start, time() + 3 * 60);
        $this->assertCount(3, $records, '->findBy() returns all previously added records');
        $this->assertEquals($records[0]['token'], 'time_2', '->findBy() returns records ordered by time in descendant order');
        $this->assertEquals($records[1]['token'], 'time_1', '->findBy() returns records ordered by time in descendant order');
        $this->assertEquals($records[2]['token'], 'time_0', '->findBy() returns records ordered by time in descendant order');

        $records = $this->getStorage()->findBy(array(), 3, $start, time() + 2 * 60);
        $this->assertCount(2, $records, '->findBy() should return only first two of the previously added records');
    }

    public function testRetrieveByEmptyCriteria()
    {
        for ($i = 0; $i < 5; ++$i) {
            $profile = new HttpProfile('token_'.$i, null, null, 'GET', 200);
            $this->getStorage()->write($profile);
        }
        $this->assertCount(5, $this->getStorage()->findBy(array(), 10), '->findBy() returns all previously added records');
        $this->getStorage()->purge();
    }

    public function testRetrieveByMethodAndLimit()
    {
        foreach (array('POST', 'GET') as $method) {
            for ($i = 0; $i < 5; ++$i) {
                $profile = new HttpProfile('token_'.$i.$method, '127.0.0.1', 'http://foo.bar', $method, 200);
                $this->getStorage()->write($profile);
            }
        }

        $this->assertCount(5, $this->getStorage()->findBy(array('method' => 'POST'), 5));

        $this->getStorage()->purge();
    }

    public function testPurge()
    {
        $profile = new HttpProfile('token1', '127.0.0.1', 'http://foo.bar', 'GET', 200);
        $this->getStorage()->write($profile);

        $this->assertTrue(false !== $this->getStorage()->read('token1'));
        $this->assertCount(1, $this->getStorage()->findBy(array('ip' => '127.0.0.1'), 10));

        $profile = new HttpProfile('token2', '127.0.0.1', 'http://example.net', 'GET', 200);
        $this->getStorage()->write($profile);

        $this->assertTrue(false !== $this->getStorage()->read('token2'));
        $this->assertCount(2, $this->getStorage()->findBy(array('ip' => '127.0.0.1'), 10));

        $this->getStorage()->purge();

        $this->assertEmpty($this->getStorage()->read('token'), '->purge() removes all data stored by profiler');
        $this->assertCount(0, $this->getStorage()->findBy(array('ip' => '127.0.0.1'), 10), '->purge() removes all items from index');
    }

    public function testDuplicates()
    {
        for ($i = 1; $i <= 5; ++$i) {
            $profile = new HttpProfile('token'.$i, '127.0.0.1', 'http://example.net', 'GET', 200);

            ///three duplicates
            $this->getStorage()->write($profile);
            $this->getStorage()->write($profile);
            $this->getStorage()->write($profile);
        }
        $this->assertCount(3, $this->getStorage()->findBy(array('ip' => '127.0.0.1', 'url' => 'http://example.net'), 3), '->findBy() method returns incorrect number of entries');
    }

    public function testStatusCode()
    {
        $profile = new HttpProfile('token_200', '127.0.0.1', 'http://example.net', 'GET', 200);
        $this->getStorage()->write($profile);

        $profile = new HttpProfile('token_404', '127.0.0.1', 'http://example.net', 'GET', 404);
        $this->getStorage()->write($profile);

        $tokens = $this->getStorage()->findBy(array(), 10);
        $this->assertCount(2, $tokens);
        $this->assertContains($tokens[0]['status_code'], array(200, 404));
        $this->assertContains($tokens[1]['status_code'], array(200, 404));
    }

    /**
     * @return \Symfony\Component\Profiler\ProfilerStorageInterface
     */
    abstract protected function getStorage();
}
