<?php

namespace Infira\Console\Machine;

use Infira\Console\Exceptions\ConsoleRuntimeException;
use Infira\Console\Helper\ProcessMessage;
use Infira\Console\Machine\Concerns\RSyncInstance;
use Infira\Console\Machine\Config\MachineConfig;
use Infira\Console\Output\Console;
use Infira\Console\Process;
use Wolo\File\FileHandler;
use Wolo\File\Path;
use Wolo\Str;

abstract class MachineInstance
{
    public readonly MachineConfig $config;

    public function __construct(protected readonly Console $console, array|MachineConfig $config = [])
    {
        $this->config = $config instanceof MachineConfig ? $config : new MachineConfig($config);
    }

    public function getName(): string
    {
        return $this->config->getName();
    }

    public function getHostOrName(): string
    {
        if ($this->config->has('host')) {
            return $this->config->getHost();
        }

        return $this->getName();
    }

    public function process(string|array $commands, ?string $cwd = null, ?array $env = null, mixed $input = null, ?float $timeout = 60): Process
    {
        //debugName('processCommand', $this->getProcessCommand($commands));

        return Process::fromShellCommandline(
            $this->getProcessCommand($commands),
            $cwd,
            $env,
            $input,
            $timeout
        )
            ->setTimeout(1800)
            ->setConsole($this->console)
            ->setSpeaker(fn(ProcessMessage $message) => $message->eachLine(function ($line) use ($message) {
                $line = trim($line);
                if ($message->isRuntimeError()) {
                    if ($message->process->canDisplayRuntimeErrors()) {
                        $line = "<fg=red>[ERROR] $line</>";
                    }
                    $message->process->setAsFailed()->stop(0);
                }
                if ($name = $message->process->getName()) {
                    $line = "<title>{$name}</title>: $line";
                }
                $this->console->writeSection(
                    $message->process->getTask() ?: $this->getHostOrName(),
                    $line,
                    'section'
                );
            }));
    }

    public function execute(array|string $commands): Process
    {
        $process = $this->process($commands);
        $process->run();

        return $process;
    }

    public function rsync(string $src, string $destination, array $options = []): Process
    {
        $extraOptions = implode(' ', $options);

        return $this->process("rsync --timeout=0 -av --progress $extraOptions $src $destination");
    }

    public function folderRSync(string $src, string $destination): Process
    {
        $src = Path::slash($src);
        $destination = Path::slash($destination);
        if (!Str::endsWith($src, '*')) {
            $src .= '*';
        }

        return $this->rsync($src, $destination, ['--del']);
    }

    public function tempPath(string ...$path): string
    {
        return Path::join($this->config->getTempPath(), ...$path);
    }

    //region files
    public function rSyncInstance(string|FileHandler $path): RSyncInstance
    {
        return new RSyncInstance($path, $this);
    }

    public function deleteFile(string ...$files): Process
    {
        if (!$files) {
            throw new ConsoleRuntimeException("no files specified");
        }
        $commands = [];
        foreach ($files as $file) {
            $commands[] = "rm -f $file";
        }

        return $this->process($commands);
    }

    public function downloadFile(string|FileHandler $source, string|FileHandler $destionation): Process
    {
        return $this->rsync($source, $destionation);
    }

    public function downloadFolder(string|FileHandler $source, string|FileHandler $destionation): Process
    {
        return $this->folderRSync($source, $destionation);
    }

    public function upload(string|FileHandler $source, string|FileHandler $destionation): Process
    {
        return $this->rsync($source, $destionation);
    }

    public function getRSyncPath(string|FileHandler $path): string
    {
        return "$path";
    }

    //endregion files

    abstract public function getProcessCommand(string|array $command, array $options = []): string;
}