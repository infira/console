<?php

namespace Infira\Console\Output;

use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleOutputWrapper
{
    private array $messages = [];
    private string $id;

    public function __construct(private $wrap, public ConsoleSectionOutput $section, private ?int $maxItems = null)
    {
        $this->id = uniqid('', true);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function write(iterable|string $messages, bool $newline = false, int $options = OutputInterface::OUTPUT_NORMAL): void
    {
        $this->messages[] = func_get_args();
        $this->wrap($newline);
    }

    public function addSubRegionContent(ConsoleOutputWrapper $region): void
    {
        $this->messages[$region->getId()] = [$region->section->getContent()];
        $this->wrap();
    }

    private function wrap(bool $newline = false): void
    {
        $this->section->clear();
        $this->section->writeln($this->wrap, true);
        $this->nl();
        if ($this->maxItems !== null && count($this->messages) >= $this->maxItems) {
            array_shift($this->messages);
        }
        $firstKey = array_key_first($this->messages);
        foreach ($this->messages as $key => $msg) {
            if (!is_int($key) && $key !== $firstKey) { //it is sub section
                $this->nl();
            }
            $this->section->writeln(...$msg);
        }
        if ($newline) {
            $this->nl();
        }
        $this->section->writeln($this->wrap, true);
    }

    private function nl(): void
    {
        $this->section->writeln('', true);//empty line

    }
}
