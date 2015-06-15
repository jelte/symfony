<?php


namespace Symfony\Component\Profiler\Tests\EventListener;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Profiler\EventListener\ConsoleProfilerListener;

class ConsoleProfilerListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test a master and sub request with an exception and `onlyException` profiler option enabled.
     */
    public function testConsoleTerminate()
    {
        $profiler = $this->getMockBuilder('Symfony\Component\Profiler\Profiler')
            ->disableOriginalConstructor()
            ->getMock();

        $profile = $this->getMockBuilder('Symfony\Component\Profiler\ConsoleProfile')
            ->disableOriginalConstructor()
            ->getMock();

        $profiler->expects($this->once())
            ->method('profileCommand')
            ->will($this->returnValue($profile));

        $command = new Command('test');
        $input = new ArgvInput();
        $output = new NullOutput();

        $listener = new ConsoleProfilerListener($profiler);

        $listener->onConsoleTerminate(new ConsoleTerminateEvent($command, $input, $output, 1));
    }

    /**
     * Test a master and sub request with an exception and `onlyException` profiler option enabled.
     */
    public function testConsoleException()
    {
        $profiler = $this->getMockBuilder('Symfony\Component\Profiler\Profiler')
            ->disableOriginalConstructor()
            ->getMock();

        $profile = $this->getMockBuilder('Symfony\Component\Profiler\ConsoleProfile')
            ->disableOriginalConstructor()
            ->getMock();

        $profiler->expects($this->once())
            ->method('profileCommand')
            ->will($this->returnValue($profile));

        $command = new Command('test');
        $input = new ArgvInput();
        $output = new NullOutput();

        $listener = new ConsoleProfilerListener($profiler);

        $listener->onConsoleException(new ConsoleExceptionEvent($command, $input, $output, new \Exception(), 1));

        $listener->onConsoleTerminate(new ConsoleTerminateEvent($command, $input, $output, 1));
    }

    /**
     * Test a master and sub request with an exception and `onlyException` profiler option enabled.
     */
    public function testConsoleExceptionOnly()
    {
        $profiler = $this->getMockBuilder('Symfony\Component\Profiler\Profiler')
            ->disableOriginalConstructor()
            ->getMock();

        $profiler->expects($this->never())
            ->method('profileCommand');

        $command = new Command('test', true);
        $input = new ArgvInput();
        $output = new NullOutput();

        $listener = new ConsoleProfilerListener($profiler, true);

        $listener->onConsoleTerminate(new ConsoleTerminateEvent($command, $input, $output, 1));
    }

    public function testSubscribedEvents()
    {
        $events = ConsoleProfilerListener::getSubscribedEvents();
        $this->assertArrayHasKey(ConsoleEvents::TERMINATE, $events);
        $this->assertArrayHasKey(ConsoleEvents::EXCEPTION, $events);
    }
}