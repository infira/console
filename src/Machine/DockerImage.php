<?php

namespace Infira\Console\Machine;

use Illuminate\Config\Repository;
use Infira\Console\Output\ConsoleOutput;
use Infira\Console\Process;
use Wolo\File\FileHandler;

class DockerImage extends MachineInstance
{
    public function __construct(ConsoleOutput $console, array|Repository $config = [], string $name = 'docker')
    {
        parent::__construct($console, $config, $name);
    }

    private function prepareMysqlCommand(string $extra = ''): string
    {
        return sprintf('mysql -uroot -p%s%s', $this->getConfig('mysqlRootPassword'), ($extra ? " $extra" : ''));
    }

    public function mysqlQuery(string|array $query): Process
    {
        return $this->process(
            array_map(fn($q) => $this->prepareMysqlCommand('-e "'.$q.'"'),
                (array)$query)
        );
    }

    public function mysqlQueryFromFile(string $db, string|FileHandler|array $files): Process
    {
        return $this->process(
            array_map(
                fn($sql) => $this->prepareMysqlCommand("$db < $sql"),
                (array)$files,
            )
        );
    }

    public function getExecuteCommand(string $command, array $options = []): string
    {
        $extraArgs = implode(' ', [$this->getConfig('image'), ...array_values($options)]);

        return "docker exec -i $extraArgs $command";
    }

    public function getProcessCommand(string|array $command, array $options = []): string
    {
        return implode(
            ' && ',
            array_map(
                static fn(string $cmd) => $this->getExecuteCommand($cmd),
                (array)$command
            )
        );
    }

    public function execute(string|array $commands): string
    {
        $res = [];
        foreach ((array)$commands as $cmd) {
            $output = null;
            exec($this->getExecuteCommand($cmd), $output);
            $res[] = $output;
        }

        return implode("\n", $res);
    }
}