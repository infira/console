<?php

namespace Infira\Console\Output;

use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Wolo\VarDumper;

class ConsoleOutput extends \Symfony\Component\Console\Output\ConsoleOutput
{
    private SymfonyStyle $style;

    private string $globalPrefix = '';

    public function __construct(InputInterface $input, int $verbosity = OutputInterface::VERBOSITY_NORMAL, bool $decorated = null, OutputFormatterInterface $formatter = null)
    {
        parent::__construct($verbosity, $decorated, $formatter);
        $this->style = new SymfonyStyle($input, $this);

        $outputStyle = new OutputFormatterStyle('magenta');
        $this->getFormatter()->setStyle('title', $outputStyle);
    }

    public function info(string $msg): static
    {
        $this->say("<info>$msg</info>");

        return $this;
    }

    public function title(string $msg): static
    {
        $this->say("<title>$msg</title>");

        return $this;
    }

    public function comment(string $msg): static
    {
        $this->say("<comment>$msg</comment>");

        return $this;
    }

    public function msg(string $msg): static
    {
        $this->say($msg);

        return $this;
    }

    public function nl(): static
    {
        $gp = $this->globalPrefix;
        $this->globalPrefix = '';
        //$this->style->newLine(1);
        $this->writeln(" ");
        $this->globalPrefix = $gp;

        return $this;
    }

    public function cl(): static
    {
        $gp = $this->globalPrefix;
        $this->globalPrefix = '';
        $cursor = new Cursor($this);
        $cursor->clearLine();
        $cursor->moveToColumn(1);
        $this->globalPrefix = $gp;

        return $this;
    }

    public function error(string $msg): static
    {
        $this->style->error($msg);

        return $this;
    }

    public function say(string $message): static
    {
        $ex = preg_split('/\r\n|\r|\n/', $message);
        array_map(function ($line) {
            $line = trim($line);
            $origLine = $line;
            if ($line) {
                $line = str_replace('<nl/>', '', $line);
                $this->writeln($line);
                if (str_contains($origLine, '<nl/>')) {
                    $this->nl();
                }
            }
        }, $ex, array_keys($ex));

        return $this;
    }

    public function sayWho(string $msg, string $saysWho): static
    {
        $msg = $this->into1Line($msg);
        if (!$msg) {
            return $this;
        }

        $msg = trim($msg);
        $msg = $msg ? " $msg" : '';
        //$title = $this->sayTitle ? "<title> $this->sayTitle </title>" : '';
        $msg = "<fg=black;bg=bright-yellow>$saysWho: </>$msg";
        $this->say($msg);

        return $this;
    }

    public function write($messages, bool $newline = false, int $options = self::OUTPUT_NORMAL): static
    {
        if (!is_iterable($messages)) {
            $messages = [$messages];
        }
        foreach ($messages as $k => $message) {
            $messages[$k] = $this->globalPrefix ? $this->globalPrefix.$message : $message;
        }
        parent::write($messages, $newline, $options);

        return $this;
    }

    public function region(string $region, callable $regionProcess): static
    {
        $msg = str_repeat("=", 25);
        $msg .= "[<question> $region </question>]";
        $msg .= str_repeat("=", 25);
        $this->comment($msg);
        $this->nl();
        $regionProcess();
        $this->nl();
        $this->comment($msg);

        return $this;
    }

    public function blink(string $msg): static
    {
        $outputStyle = new OutputFormatterStyle('red', '#ff0', ['bold', 'blink']);
        $this->getFormatter()->setStyle('fire', $outputStyle);
        $this->writeln("<fire>$msg</>");

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

    public function traceRegion(string $regionTitle = 'trace', array $trace = null): void
    {
        $trace = $trace ?: debug_backtrace();
        $this->nl()->region($regionTitle, function () use ($trace) {
            foreach ($trace as $key => $row) {
                $key++;
                $row['file'] = $row['file'] ?? '';
                $row['line'] = $row['line'] ?? '';
                self::writeln($key.') in file <info>'.$row['file'].' </info> on line '.$row['line']);
            }
        });
    }

    public function into1Line(string $message): string
    {
        $ex = preg_split('/\r\n|\r|\n/', trim($message));
        $newLines = [];
        array_map(static function ($line) use (&$newLines) {
            $line = trim($line);
            if ($line) {
                $newLines[] = $line;
            }
        }, $ex);

        return implode("", $newLines);
    }
}