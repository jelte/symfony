<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DebugBundle\Tests\DependencyInjection\Compiler;

use Symfony\Bundle\DebugBundle\DependencyInjection\Compiler\DumpDataCollectorPass;
use Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpFoundation\RequestStack;

class DumpDataCollectorPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessWithFileLinkFormatParameter()
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new DumpDataCollectorPass());
        $container->setParameter('templating.helper.code.file_link_format', 'file-link-format');

        $definition = new Definition('Symfony\Component\VarDumper\Profiler\DumpDataCollector', array(null, null, null, null, null));
        $container->setDefinition('data_collector.dump', $definition);

        $container->compile();

        $this->assertSame('file-link-format', $definition->getArgument(2));
    }

    public function testProcessWithoutFileLinkFormatParameter()
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new DumpDataCollectorPass());

        $definition = new Definition('Symfony\Component\VarDumper\Profiler\DumpDataCollector', array(null, null, null, null, null));
        $container->setDefinition('data_collector.dump', $definition);

        $container->compile();

        $this->assertNull($definition->getArgument(1));
    }

    public function testProcessWithToolbarEnabled()
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new DumpDataCollectorPass());
        $requestStack = new RequestStack();

        $definition = new Definition('Symfony\Component\VarDumper\Profiler\DumpDataCollector', array($requestStack, null, null, null, null));
        $container->setDefinition('data_collector.dump', $definition);
        $container->setParameter('web_profiler.debug_toolbar.mode', WebDebugToolbarListener::ENABLED);

        $container->compile();

        $this->assertSame($requestStack, $definition->getArgument(0));
    }

    public function testProcessWithToolbarDisabled()
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new DumpDataCollectorPass());

        $definition = new Definition('Symfony\Component\VarDumper\Profiler\DumpDataCollector', array(new RequestStack(), null, null, null, null));
        $container->setDefinition('data_collector.dump', $definition);
        $container->setParameter('web_profiler.debug_toolbar.mode', WebDebugToolbarListener::DISABLED);

        $container->compile();

        $this->assertNull($definition->getArgument(3));
    }

    public function testProcessWithoutToolbar()
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new DumpDataCollectorPass());

        $definition = new Definition('Symfony\Component\VarDumper\Profiler\DumpDataCollector', array(new RequestStack(), null, null, null, null));
        $container->setDefinition('data_collector.dump', $definition);

        $container->compile();

        $this->assertNull($definition->getArgument(3));
    }
}
