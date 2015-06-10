<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\WebProfilerBundle\Tests\Command;

use Symfony\Bundle\WebProfilerBundle\Command\ImportCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Profiler\HttpProfile;
use Symfony\Component\Profiler\Profile;

class ImportCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $profiler = $this
            ->getMockBuilder('Symfony\Component\Profiler\HttpProfiler')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $profile = new HttpProfile('TOKEN', '127.0.0.1', 'http://foo.bar/', 'GET', 200);
        $profiler->expects($this->once())->method('import')->will($this->returnValue($profile));

        $command = new ImportCommand($profiler);
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('filename' => __DIR__.'/../Fixtures/profile.data'));
        $this->assertRegExp('/Profile "TOKEN" has been successfully imported\./', $commandTester->getDisplay());
    }
}
