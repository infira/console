<?php

namespace Infira\Console;

use Infira\Console\Output\ConsoleOutput;

class Process extends \Symfony\Component\Process\Process
{
    /**
     * @var callable|null
     */
    private $speaker;
    private ConsoleOutput $console;

    public function setConsoleOutput(ConsoleOutput $console): static
    {
        $this->console = $console;

        return $this;
    }

    public function speak(mixed ...$speakerParams): static
    {
        $speaker = $this->speaker ?: fn($line) => $this->console->writeEachLine($line);
        $this->run(fn($type, $line) => $speaker($line, ...$speakerParams));

        return $this;
    }

    public function setSpeaker(callable $speaker): static
    {
        $this->speaker = $speaker;

        return $this;
    }

    public function getOutput(): string
    {
        $this->run();

        return parent::getOutput();
    }
}