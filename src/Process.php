<?php

namespace Infira\Console;

class Process extends \Symfony\Component\Process\Process
{
    /**
     * @var callable|null
     */
    private $speaker;
    private array $speakerParameters = [];
    private ?string $task;
    private bool $failed = false;
    private bool $voidRunError = false;

    public function setAsFailed(): static
    {
        $this->failed = true;

        return $this;
    }

    public function isSuccessful(): bool
    {
        if ($this->failed) {
            return false;
        }
        if (!parent::isSuccessful()) {
            return false;
        }

        return true;
    }

    public function withTask(string $task = null): static
    {
        $this->task = $task;

        return $this;
    }

    public function runTask(string $task = null): static
    {
        $this->task = $task;
        if ($task) {
            $this->speak("task <info>$task</info> is running ....");
        }
        $this->run();


        return $this;
    }

    public function voidRunError(): static
    {
        $this->voidRunError = true;

        return $this;
    }

    public function canDisplayErrors(): bool
    {
        return !$this->voidRunError;
    }

    public function getTask(): ?string
    {
        return $this->task ?? null;
    }

    public function speakOld(mixed ...$speakerParams): static
    {
        if ($this->isRunning()) {
            $this->setSpeakerParameters($speakerParams);
        }
        else {
            $this->doSpeak(...$speakerParams);
        }

        return $this;
    }

    public function speak(mixed ...$speakerParams): static
    {
        if ($this->isRunning()) {
            $this->setSpeakerParameters($speakerParams);
        }
        else {
            $this->doSpeak(...$speakerParams);
        }

        return $this;
    }

    public function speakDone(string $message = '<fg=black;bg=green>    DONE    </>'): static
    {
        return $this->speak($message);
    }

    public function speakFailedStatus(string $message = '<error>Failed with status: {status}</error>'): static
    {
        return $this->speakStatus($message);
    }

    public function speakStatus(string $message = '{status}'): static
    {
        return $this->speak(flu($message)->render(['status' => $this->getStatus()]));
    }

    public function setSpeaker(callable $speaker): static
    {
        $this->speaker = $speaker;

        return $this;
    }

    public function setSpeakerParameters(mixed ...$arguments): static
    {
        $this->speakerParameters = $arguments;

        return $this;
    }


    //region abstractions
    protected function buildCallback(callable $callback = null): \Closure
    {
        //debug(['$callback' => $callback]);
        if ($callback === null) {
            //debug(['$this->speaker' => isset($this->speaker)]);
            if (isset($this->speaker)) {
                $callback = fn($type, $line) => $this->doSpeak($type, $line, ...$this->speakerParameters);
            }
        }

        return parent::buildCallback($callback);
    }

    protected function doSpeak(mixed ...$params)
    {
        if (!isset($this->speaker)) {
            throw new \RuntimeException('Speaker is not set');
        }

        return ($this->speaker)(...$params);
    }
    //endregion

}