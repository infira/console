<?php

namespace Infira\Console\Machine;

use Illuminate\Config\Repository;
use Infira\Console\Output\ConsoleOutput;
use Infira\Console\Process;
use Wolo\File\FileHandler;

class DockerImage extends LocalHost
{
    public function __construct(ConsoleOutput $console, array|Repository $config = [])
    {
        parent::__construct('docker', $console, $config);
    }

    private function prepareMysqlCommand(string $extra = ''): string
    {
        if (!$this->config->has('mysqlRootPassword')) {
            throw new MachineMissingConfigException('docker mysql root is not defined');
        }

        return sprintf('mysql -uroot -p%s%s', $this->config->get('mysqlRootPassword'), ($extra ? " $extra" : ''));
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

    public function getProcessCommand(string|array $command, array $options = []): string
    {
        if (!$this->config->has('image')) {
            throw new MachineMissingConfigException('docker image name is not defined');
        }
        $image = $this->config->get('image');

        return implode(
            ' && ',
            array_map(
                static fn(string $cmd) => "docker exec -i $image $cmd",
                (array)$command
            )
        );
    }
}