<?php

namespace Infira\Console\Machine;

use Infira\Console\Machine\Config\DockerImageConfig;
use Infira\Console\Process;
use Infira\Klahvik\helper\Utils;
use Wolo\File\FileHandler;

/**
 * @property-read DockerImageConfig $config
 */
class DockerImage extends MachineInstance
{
    private function prepareSqlCommand(string $extra = ''): string
    {
        return Utils::renderString(
            "{command} -u{user} -p{password}{extra}",
            [
                'command' => $this->config->getSqlCommand(),
                'user' => $this->config->getSqlRootUser(),
                'password' => $this->config->getSqlRootPassword(),
                'extra' => $extra ? " $extra" : '',
            ]
        );
    }

    public function sqlQuery(string|array $query): Process
    {
        return $this->process(
            array_map(fn($q) => $this->prepareSqlCommand('-e "'.$q.'"'),
                (array)$query)
        );
    }

    public function sqlQueryFromFile(string $db, string|FileHandler|array $files): Process
    {
        return $this->process(
            array_map(
                fn($sql) => $this->prepareSqlCommand("$db < $sql"),
                (array)$files,
            )
        );
    }

    public function getProcessCommand(string|array $command, array $options = []): string
    {
        return implode(
            ' && ',
            array_map(
                function (string $cmd) use ($options) {
                    $extraArgs = implode(' ', [$this->config->getContainer(), ...array_values($options)]);

                    return "docker exec -i $extraArgs $cmd";
                },
                (array)$command
            )
        );
    }
}