<?php

namespace Infira\Console;

use Infira\Console\Helper\ProcessMessage;

class Process extends \Symfony\Component\Process\Process
{
    /**
     * @var callable|null
     */
    private $speaker;
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

    public function speak(string|ProcessMessage $message, mixed ...$extraSpeakerParams): static
    {
        if (!isset($this->speaker)) {
            return $this;
        }

        if (is_string($message)) {
            $message = new ProcessMessage($message, $this);
        }

        return ($this->speaker)($message, ...$extraSpeakerParams);
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
        return $this->speak(str_replace('{status}', $this->getStatus(), $message));
    }

    /**
     * @template TProcessMessage
     * @template TExtraParams
     * @param  callable<TProcessMessage,<TExtraParams>>  $speaker
     * @return $this
     */
    public function setSpeaker(callable $speaker): static
    {
        $this->speaker = $speaker;

        return $this;
    }


    //region abstractions
    protected function buildCallback(callable $callback = null): \Closure
    {
        if (($callback === null) && isset($this->speaker)) {
            $callback = fn($type, $line) => $this->speak(ProcessMessage::makeRuntime($type, $line, $this));
        }

        return parent::buildCallback($callback);
    }
    //endregion

}