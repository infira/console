<?php

namespace Infira\Console;

use Infira\Console\Helper\ProcessMessage;
use Infira\Console\Output\Console;

class Process extends \Symfony\Component\Process\Process
{
    /**
     * @var callable|null
     */
    private $speaker;
    private ?string $task = null;
    private ?string $name = null;
    private bool $failed = false;
    private bool $voidDisplayRuntimeErrors = false;
    private Console $console;
    private array $voidExitCodesAsErrors = [];

    /**
     * @param Console $console
     * @return $this
     */
    public function setConsole(Console $console): static
    {
        $this->console = $console;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name ?: null;
    }

    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function setAsFailed(): static
    {
        $this->failed = true;

        return $this;
    }

    public function isSuccessful(): bool
    {
        if ($this->isExitCodeSuccess()) {
            return true;
        }

        if ($this->failed) {
            return false;
        }

        return true;
    }

    public function isFailed(): bool
    {
        // $this->console->dumpArray([
        //     'status' => $this->getStatus(),
        //     'exit_code' => $this->getExitCode(),
        //     'voidExitCodesAsErrors' => $this->voidExitCodesAsErrors,
        //     'isExitCodeSuccess' => $this->isExitCodeSuccess(),
        //     'isSuccessful' => $this->isSuccessful(),
        //     'failed' => (int)$this->failed,
        //     'in_array' => in_array($this->getExitCode(), $this->voidExitCodesAsErrors, true),
        // ]);
        return !$this->isSuccessful();
    }

    public function isExitCodeSuccess(): bool
    {
        $exitCode = $this->getExitCode();
        if ($exitCode === 0) {
            return true;
        }
        if (in_array($exitCode, $this->voidExitCodesAsErrors, true)) {
            return true;
        }

        return false;
    }

    public function withTask(?string $task = null): static
    {
        $this->task = $task;

        return $this;
    }

    public function runTask(?string $task = null): static
    {
        $this->task = $task;
        if ($task) {
            $this->speak("task <info>$task</info> is running ....");
        }
        $this->run();

        return $this;
    }

    public function voidDIsplayRuntimeErrors(): static
    {
        $this->voidDisplayRuntimeErrors = true;

        return $this;
    }

    public function voidExitCodesAsErrors(array|int $codes): static
    {
        array_push($this->voidExitCodesAsErrors, ...((array)$codes));

        return $this;
    }

    public function canDisplayRuntimeErrors(): bool
    {
        return !$this->voidDisplayRuntimeErrors;
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

        ($this->speaker)($message, ...$extraSpeakerParams);

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
        return $this->speak(str_replace('{status}', $this->getStatus(), $message));
    }

    /**
     * @template TProcessMessage
     * @template TExtraParams
     * @param callable<TProcessMessage,<TExtraParams>> $speaker
     * @return $this
     */
    public function setSpeaker(callable $speaker): static
    {
        $this->speaker = $speaker;

        return $this;
    }

    //region abstractions
    protected function buildCallback(?callable $callback = null): \Closure
    {
        if (($callback === null) && isset($this->speaker)) {
            $callback = fn($type, $line) => $this->speak(
                new ProcessMessage($line, $this, $type)
            );
        }

        return parent::buildCallback($callback);
    }
    //endregion

}