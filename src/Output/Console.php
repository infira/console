<?php

namespace Infira\Console\Output;

use Infira\Console\Utils;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Wolo\VarDumper;

class Console extends ConsoleOutput
{
    use Traits\ConsoleRegion;

    public SymfonyStyle $style;
    public Cursor $cursor;
    private array $memorySections = [];

    public function __construct(InputInterface $input, int $verbosity = OutputInterface::VERBOSITY_NORMAL, bool $decorated = null, OutputFormatterInterface $formatter = null)
    {
        parent::__construct(
            $verbosity,
            $decorated,
            $formatter
        );
        $this->style = new SymfonyStyle($input, $this);
        $outputStyle = new OutputFormatterStyle('magenta');
        $this->getFormatter()->setStyle('title', $outputStyle);
        $this->cursor = new Cursor($this);
    }

    public function writeSection(string $section, string $message, string $style = 'info'): static
    {
        $formatter = new FormatterHelper();
        $formattedLine = $formatter->formatSection($section, $message, $style);
        $this->writeln($formattedLine);

        return $this;
    }

    public function dumpArray(array $arr): static
    {
        $this->writeln(VarDumper::console($arr));

        return $this;
    }

    public function debug(...$var): static
    {
        foreach ($var as $v) {
            $this->nl()->writeln(VarDumper::console($v));
        }

        return $this;
    }

    public function trace(): static
    {
        return $this->dumpArray(debug_backtrace());
    }

    /**
     * @template TTraceItem
     * @param  array  $trace
     * @param  callable<TTraceItem>|null  $formatter
     * @return void
     */
    public function dumpTrace(array $trace, callable $formatter = null): void
    {
        foreach ($trace as $key => $row) {
            $key++;
            if ($formatter) {
                $this->writeln($formatter($row));
            }
            else {
                $file = $row['file'] ?? '';
                $line = $row['line'] ?? '';
                $this->writeln("$key) in file <info>$file:$line</info> on line");
            }
        }
    }

    public function writeEachLine(string|array $message): static
    {
        Utils::eachLine($message, static fn($line) => $this->writeln($line));

        return $this;
    }

    public function write(iterable|string $messages, bool $newline = false, int $options = self::OUTPUT_NORMAL): void
    {
        if ($this->regions) {
            $count = count($this->regions);

            $last = end($this->regions);
            $last->write(...func_get_args());
            if ($count > 1) {
                $current = $this->regions[0];
                foreach (array_slice($this->regions, 1) as $region) {
                    $current->addSubRegionContent($region);
                    $current = $region;
                }
            }

            return;
        }
        parent::write($messages, $newline, $options);
    }

    //region style shortcuts
    public function nl(int $lines = 1): static //add new line
    {
        $this->style->newLine($lines);

        return $this;
    }

    public function clearLine(int $lines = 1): static
    {
        $this->cursor->moveUp($lines)->clearLineAfter();

        return $this;
    }

    public function error(string $msg): static
    {
        $this->style->error($msg);

        return $this;
    }

    public function blink(string $msg): static
    {
        $outputStyle = new OutputFormatterStyle('red', '#ff0', ['bold', 'blink']);
        $this->getFormatter()->setStyle('fire', $outputStyle);
        $this->writeln("<fire>$msg</>");

        return $this;
    }

    //endregion

    //region wrapping console outputs
    public function createWrapper(string $wrap, bool $useMemory = false, int $maxItems = null): ConsoleOutputWrapper
    {
        return new ConsoleOutputWrapper(
            $wrap,
            $useMemory ? $this->createMemorySection() : $this->section(),
            $maxItems
        );
    }

    private function addWrapper(string $wrap, int $maxItems = null): void
    {
        $isFirst = count($this->regions) === 0;
        $this->regions[] = $this->createWrapper(
            $wrap,
            !$isFirst,
            $maxItems
        );
    }

    private function createMemorySection(): ConsoleSectionOutput
    {
        return new ConsoleSectionOutput(fopen('php://memory', 'wb', false), $this->memorySections, $this->getVerbosity(), $this->isDecorated(), $this->getFormatter());
    }

    private function popWrapper(): void
    {
        array_pop($this->regions);
    }
    //endregion
}