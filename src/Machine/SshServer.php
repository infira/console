<?php

namespace Infira\Console\Machine;

use Illuminate\Config\Repository;
use Infira\Console\Output\ConsoleOutput;
use Infira\Console\Process;
use Spatie\Ssh\Ssh;
use Wolo\File\FileHandler;

class SshServer extends MachineInstance
{
    private LocalHost $local;

    public function __construct(ConsoleOutput $console, array|Repository $config = [], LocalHost $local = null, string $name = 'sshServer')
    {
        parent::__construct($name, $console, $config);
        if ($local === null) {
            $this->local = new LocalHost("$name.local", $console);
        }
    }

    /**
     * @return string - returns user@host
     */
    public function getUserHost(): string
    {
        return sprintf("%s@%s", $this->config->get('user'), $this->config->get('host'));
    }

    public function upload(string|FileHandler $localPath, string|FileHandler $remotePath): Process
    {
        return $this->local->rsync(
            (string)$localPath,
            $this->getRsyncSshArgument($remotePath),
        );
    }

    public function download(string|FileHandler $remotePath, string|FileHandler $localPath): Process
    {
        return $this->local->rsync(
            $this->getRsyncSshArgument($remotePath),
            (string)$localPath
        );
    }

    public function downloadFolder(string|FileHandler $remotePath, string|FileHandler $localPath): Process
    {
        return $this->local->folderRSync($this->getRsyncSshArgument($remotePath), $localPath);
    }

    public function deleteFile(string ...$files): void
    {
        if (!$files) {
            return;
        }
        $commands = [];
        foreach ($files as $file) {
            $commands[] = "rm -f $file";
        }
        $this->execute($commands);
    }

    public function execute(array|string $commands, string $taskName = null): Process
    {
        $process = $this->process($commands);
        $process->withTask($taskName);
        $process->run();

        return $process;
    }

    /**
     * Create an SSH instance
     *
     * @return Ssh
     */
    public function ssh(): Ssh
    {
        return Ssh::create($this->getConfig('user'), $this->getConfig('host'), $this->getConfig('post'));
    }

    public function getProcessCommand(string|array $command, array $options = []): string
    {
        return $this->ssh()->getExecuteCommand($command);
    }

    private function getRsyncSshArgument(string|FileHandler $path): string
    {
        $server = $this->getUserHost();

        return "$server:$path";
    }

}