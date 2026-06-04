<?php

namespace Infira\Console\Machine;

use Infira\Console\Machine\Config\LocalhostConfig;
use Wolo\File\FileHandler;

/**
 * @property-read LocalhostConfig $config
 */
class Localhost extends MachineInstance
{
    public function getProcessCommand(string|array $command, array $options = []): string
    {
        //return implode('&', (array)$command);
        $commandString = implode(PHP_EOL, (array)$command);
        $delimiter = 'EOF-LOCAL-CMD';

        return "sh << $delimiter".PHP_EOL
               .$commandString.PHP_EOL
               .$delimiter;
    }

    public function tempFile(string ...$path): FileHandler
    {
        return new FileHandler($this->tempPath(...$path));
    }
}