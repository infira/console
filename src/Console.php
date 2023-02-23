<?php

namespace Infira\Console;

use Infira\Console\Output\ConsoleOutput;

/**
 * @mixin ConsoleOutput
 * @method static ConsoleOutput writeSection(string $section, string $message, bool $newLine = false)
 * @method static ConsoleOutput region(string $title, callable $process, int $maxItems = null)
 * @method static ConsoleOutput miniRegion(string $title, callable $process, int $maxItems = null)
 * @method static ConsoleOutput dumpArray(array $arr)
 * @method static ConsoleOutput debug(...$var)
 * @method static ConsoleOutput trace()
 * @method static void dumpTrace(array $trace,bool $formatPHPStormFileLinks = true)
 * @method static ConsoleOutput writeEachLine(string|array $message)
 * @method static void write(iterable|string $messages, bool $newline = false, int $options = self::OUTPUT_NORMAL)
 * @method static void writeln(string|iterable $messages, int $options = self::OUTPUT_NORMAL)
 * @method static ConsoleOutput nl(int $lines = 1)
 * @method static ConsoleOutput clearLine(int $lines = 1)
 * @method static ConsoleOutput blink(string $msg)
 */
class Console
{
    /**
     * @var ConsoleOutput
     */
    public static ConsoleOutput $output;

    public static function __callStatic(string $name, array $arguments)
    {
        return self::$output->$name(...$arguments);
    }

    /**
     * @param  string  $msg
     * @param  mixed|null  $data
     * @return void
     * @throws ConsoleRuntimeException
     */
    public static function error(string $msg, mixed $data = null): void
    {
        $exception = new ConsoleRuntimeException($msg);
        if ($data !== null) {
            $exception = $exception->with($data);
        }
        throw $exception;
    }
}
