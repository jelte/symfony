<?php

namespace Symfony\Component\Profiler;

class ConsoleProfile extends AbstractProfile
{
    private $command;
    private $arguments;
    private $options;
    private $exitCode;

    public function __construct($token, $command, array $arguments, array $options, $exitCode, $time = null)
    {
        parent::__construct($token, $time);
        $this->command = $command;
        $this->arguments = $arguments;
        $this->options = $options;
        $this->exitCode = $exitCode;
    }

    public function getCommand()
    {
        return $this->command;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getExitCode()
    {
        return $this->exitCode;
    }
}
