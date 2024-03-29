<?php

namespace Infira\Console\Machine;

use Illuminate\Config\Repository;
use Infira\Console\Helper\ProcessMessage;
use Infira\Console\Output\Console;
use Infira\Console\Process;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Wolo\File\FileHandler;
use Wolo\File\Path;
use Wolo\Str;

abstract class MachineInstance
{
    private string $name;
    public Repository $config;

    public function __construct(protected Console $console, array|Repository $config = [], string $name = 'machine')
    {
        $this->name = $name;
        //black, red, green, yellow, blue, magenta, cyan, white, default, gray, bright-red, bright-green, bright-yellow, bright-blue, bright-magenta, bright-cyan, bright-white
        $this->console->getFormatter()->setStyle('task', new OutputFormatterStyle('bright-cyan'));
        $this->config = new Repository($config);
    }

    public function task(string $title, callable $between): void
    {
        $title = str_replace('{name}', '<fg=cyan>'.$this->name.'</>', $title);
        $this->console->miniRegion($title, $between);
    }

    public function process(string|array $commands, string $cwd = null, array $env = null, mixed $input = null, ?float $timeout = 60): Process
    {
        return Process::fromShellCommandline(
            $this->getProcessCommand($commands),
            $cwd,
            $env,
            $input,
            $timeout
        )
            ->setTimeout(1800)
            ->setSpeaker(
                fn(ProcessMessage $message) => $message->eachLine(function ($line) use ($message) {
                    $line = trim($line);
                    if ($message->isRuntimeError() && $message->process->canDisplayErrors()) {
                        $line = "<fg=red>[ERROR] $line</>";
                        $message->process->setAsFailed()->stop(0);
                    }
                    $this->console->writeSection(
                        $message->process->getTask() ?: $this->name,
                        $line,
                        'task'
                    );
                })
            );
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

    public function tmpPath(string ...$path): string
    {
        return Path::join($this->getConfig('tmpPath'), ...$path);
    }

    public function tmpFile(string ...$path): FileHandler
    {
        return new FileHandler($this->tmpPath(...$path));
    }

    protected function getConfig(string $name): mixed
    {
        if (!$this->config->has($name)) {
            throw new MachineMissingConfigException("config('$name') is not defined");
        }

        return $this->config->get($name);
    }

    abstract public function getProcessCommand(string|array $command, array $options = []): string;

    abstract public function execute(string|array $commands): mixed;
}