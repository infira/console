<?php

namespace Infira\Console\Helper;

use Infira\Console\Process;

class ProcessMessage implements \Stringable
{
    public function __construct(public string $message, public ?Process $process = null) {}

    public function setProcess(Process $process): static
    {
        $this->process = $process;

        return $this;
    }

    public function __toString()
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return $this->message;
    }
}