<?php

namespace Symfony\Component\Profiler;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Profiler\Storage\ProfilerStorageInterface;

class ConsoleProfiler extends AbstractProfiler
{
    private $commands = array();
    private $inputs;
    private $exitCodes;

    public function __construct(ProfilerStorageInterface $profileStorage)
    {
        parent::__construct($profileStorage);

        $this->inputs = new \SplObjectStorage();
        $this->exitCodes = new \SplObjectStorage();
    }

    /**
     * @return ProfileInterface
     */
    protected function createProfile()
    {
        /** @var Command $command */
        if (null === ($command = array_shift($this->commands))) {
            return;
        }
        /** @var InputInterface $input */
        $input = $this->inputs[$command];

        return new ConsoleProfile($this->generateToken(), $command->getName(), $input->getArguments(), $input->getOptions(), $this->exitCodes[$command]);
    }

    public function addCommand(Command $command, InputInterface $input, $exitCode)
    {
        $this->commands[] = $command;
        $this->inputs[$command] = $input;
        $this->exitCodes[$command] = $exitCode;
    }
}
