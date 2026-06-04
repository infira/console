<?php

namespace Infira\Console\Machine;

use Infira\Console\Machine\Config\SshServerConfig;
use Spatie\Ssh\Ssh;
use Wolo\File\FileHandler;

/**
 * @property-read SshServerConfig $config
 */
class SshServer extends MachineInstance
{
    /**
     * Create an SSH instance
     *
     * @return Ssh
     */
    public function ssh(): Ssh
    {
        return Ssh::create($this->config->getUser(), $this->config->getHost(), $this->config->getPort());
    }

    public function getProcessCommand(string|array $command, array $options = []): string
    {
        return $this->ssh()->getExecuteCommand($command);
    }

    public function getRSyncPath(string|FileHandler $path): string
    {
        return "{$this->config->getUser()}@{$this->config->getHost()}:$path";
    }

}