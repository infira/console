<?php

namespace Infira\Console;

use Infira\Console\Output\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends \Symfony\Component\Console\Command\Command
{
    public Console $console;
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
        $this->console = &$output;
        $this->input = &$input;
        $this->configureExecute();
        $this->beforeExecute();
        $this->runCommand();
        $this->afterExecute();

        return $this->success();
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addOption('region-max-lines', null, InputOption::VALUE_REQUIRED);
    }

    protected function success(): int
    {
        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    protected function configureExecute()
    {
        if ($this->input->hasOption('region-max-lines')) {
            $this->console->setRegionMaxItems($this->input->getOption('region-max-lines'));
        }
    }

    protected function beforeExecute() {}

    protected function runCommand() {}

    protected function afterExecute()
    {
        //void
    }
}