<?php

namespace Infira\console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends \Symfony\Component\Console\Command\Command
{
    /**
     * @var \Infira\console\ConsoleOutput
     */
    public $output;
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @param  InputInterface  $input
     * @param  OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        set_time_limit(7200);
        $this->output = &$output;
        $this->input = &$input;
        $this->configureExecute();
        $this->beforeExecute();
        $this->runCommand();
        $this->afterExecute();

        return $this->success();
    }

    protected function success(): int
    {
        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    protected function configureExecute() {}

    protected function beforeExecute() {}

    protected function runCommand() {}

    protected function afterExecute()
    {
        //void
    }
}