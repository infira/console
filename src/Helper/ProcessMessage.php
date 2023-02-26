<?php

namespace Infira\Console\Helper;

use Infira\Console\Process;
use Infira\Console\Utils;
use Stringable;
use Symfony\Component\Process\Process as SymfonyProcess;

class ProcessMessage implements Stringable
{
    private ?string $stdOutType = null;
    private bool $isRunning = false;

    public function __construct(public string $message, public Process $process) {}

    public static function makeRuntime(string $type, string $message, Process $process): static
    {
        return (new static($message, $process))
            ->setRuntime(true)
            ->setRuntimeOutType($type);
    }

    public function setProcess(Process $process): static
    {
        $this->process = $process;

        return $this;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return $this->message;
    }


    /**
     * @return string|null SymfonyProcess::OUT || SymfonyProcess::ERR
     */
    public function getRuntimeOutType(): ?string
    {
        return $this->stdOutType;
    }

    private function setRuntimeOutType(string $type): static
    {
        $this->stdOutType = $type;

        return $this;
    }

    public function isRuntimeError(): bool
    {
        return $this->stdOutType === SymfonyProcess::ERR;
    }

    /**
     * Was message constructed in while process is running
     *
     * @return bool
     */
    public function isRuntime(): bool
    {
        return $this->isRunning;
    }

    public function setRuntime(bool $bool): static
    {
        $this->isRunning = $bool;

        return $this;
    }

    /**
     * Split message to lines ant iterate over with a callback
     *
     * @return $this
     */
    public function eachLine(callable $callback): static
    {
        Utils::eachLine($this->message, $callback);

        return $this;
    }

}