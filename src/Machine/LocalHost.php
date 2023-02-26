<?php

namespace Infira\Console\Machine;

class LocalHost extends MachineInstance
{
    public function getProcessCommand(string|array $command, array $options = []): string
    {
        $commandString = implode(PHP_EOL, (array)$command);
        $delimiter = 'EOF-LOCAL-CMD';

        return "sh << $delimiter".PHP_EOL
            .$commandString.PHP_EOL
            .$delimiter;
    }

    public function execute(string|array $commands): string
    {
        $res = [];
        foreach ((array)$commands as $cmd) {
            $output = null;
            exec($cmd, $output);
            $res[] = $output;
        }

        return implode("\n", $res);
    }
}