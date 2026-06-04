<?php

namespace Infira\Console\Output;

use Infira\Console\Process;
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
    private ?int $defaultRegionMaxItems = null;
    /**
     * @var ConsoleOutputWrapper[]
     */
    private array $regions = [];

    public SymfonyStyle $style;
    public Cursor $cursor;
    private array $memorySections = [];

    public function __construct(InputInterface $input, int $verbosity = OutputInterface::VERBOSITY_NORMAL, ?bool $decorated = null, ?OutputFormatterInterface $formatter = null)
    {
        parent::__construct(
            $verbosity,
            $decorated,
            $formatter
        );
        $this->style = new SymfonyStyle($input, $this);
        $this->setStyles();
        $this->cursor = new Cursor($this);
    }

    private function setStyles(): void
    {
        //black, red, green, yellow, blue, magenta, cyan, white, default, gray, bright-red, bright-green, bright-yellow, bright-blue, bright-magenta, bright-cyan, bright-white
        $formatter = $this->getFormatter();
        $formatter->setStyle('title', new OutputFormatterStyle('magenta'));
        $formatter->setStyle('fire', new OutputFormatterStyle('red', '#ff0', ['bold', 'blink']));
        $formatter->setStyle('section', new OutputFormatterStyle('bright-cyan'));
        $formatter->setStyle('name', new OutputFormatterStyle('cyan'));
    }

    //region debugging
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
     * @param array $trace
     * @param callable<TTraceItem>|null $formatter
     * @return void
     */
    public function dumpTrace(array $trace, ?callable $formatter = null): void
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

    //endregion debugging

    public function write(iterable|string $messages, bool $newline = false, int $options = self::OUTPUT_NORMAL): void
    {
        if ($this->regions) {
            $count = count($this->regions);

            $last = end($this->regions);
            $last->write(...func_get_args());
            if ($count > 1) {
                $current = $this->regions[0];
                foreach (array_slice($this->regions, 1) as $region) {
                    $current->writeRegion($region);
                    $current = $region;
                }
            }

            return;
        }
        parent::write($messages, $newline, $options);
    }

    public function clearLine(int $lines = 1): static
    {
        $this->cursor->moveUp($lines)->clearLineAfter();

        return $this;
    }

    //region style shortcuts

    public function nl(int $lines = 1): static //add new line
    {
        $this->style->newLine($lines);

        return $this;
    }

    public function error(string $msg): static
    {
        $this->style->error($msg);

        return $this;
    }

    public function blink(string $msg): static
    {
        $this->writeln("<fire>$msg</fire>");

        return $this;
    }

    //endregion

    //region wrapping console outputs
    public function memorySection(): ConsoleSectionOutput
    {
        return new ConsoleSectionOutput(
            fopen('php://memory', 'wb', false),
            $this->memorySections,
            $this->getVerbosity(),
            $this->isDecorated(),
            $this->getFormatter()
        );
    }

    private function popWrapper(): void
    {
        array_pop($this->regions);
    }

    //endregion

    //region & sections
    /**
     * @param string $title
     * @param 'full'|'mini' $type
     * @param callable $process
     * @param int|null $maxItems
     * @return $this
     */
    private function doRegion(string $title, string $type, callable $process, ?int $maxItems = null): static
    {
        $isFirst = count($this->regions) === 0;
        $this->regions[] = new ConsoleOutputWrapper(
            $title,
            $type,
            !$isFirst ? $this->memorySection() : $this->section(),
            $maxItems ?? $this->getRegionMaxItems()
        );
        $process();
        $this->popWrapper();

        return $this;
    }

    /**
     * @param string $title
     * @param callable $process - while region is open every output send to console will be caught
     * @param int|null $maxItems
     * @return $this
     */
    public function region(string $title, callable $process, ?int $maxItems = null): static
    {
        return $this->doRegion(
            $title,
            'full',
            $process,
            $maxItems
        );
    }

    /**
     * @param string $title
     * @param callable $process - while region is open every output send to console will be caught
     * @param int|null $maxItems
     * @return $this
     */
    public function miniRegion(string $title, callable $process, ?int $maxItems = null): static
    {
        return $this->doRegion(
            $title,
            'mini',
            $process,
            $maxItems
        );
    }

    public function writeSection(string $section, string $message, string $style = 'info'): static
    {
        $this->writeln(
            new FormatterHelper()->formatSection($section, $message, $style)
        );

        return $this;
    }

    private function getRegionMaxItems(): ?int
    {
        return $this->defaultRegionMaxItems ?? null;
    }

    public function setRegionMaxItems(?int $items): static
    {
        $this->defaultRegionMaxItems = $items;

        return $this;
    }

    //endregion region & sections

    /**
     * @param callable|Process|callable[]|Process[] $process
     * @return void
     */
    public function run(callable|Process|array $process): void
    {
        foreach ($process as $item) {
            if ($item instanceof Process) {
                $item->run();
                if ($item->isFailed()) {
                    $item->speakFailedStatus();
                    break;
                }
                $item->speakDone();
            }
            else {
                if ($item() === false) {
                    break;
                }
            }
        }
    }
}