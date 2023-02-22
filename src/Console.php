<?php

namespace Infira\Console;

use Infira\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method static ConsoleOutput info(string $msg)
 * @method static ConsoleOutput title(string $msg)
 * @method static ConsoleOutput comment(string $msg)
 * @method static ConsoleOutput msg(string $msg)
 * @method static ConsoleOutput nl()
 * @method static ConsoleOutput cl()
 * @method static ConsoleOutput say(string $message)
 * @method static ConsoleOutput sayWho(string $msg, string $saysWho)
 * @method static ConsoleOutput write($messages, bool $newline = false, int $options = OutputInterface::OUTPUT_NORMAL)
 * @method static ConsoleOutput region(string $region, callable $regionProcess)
 * @method static ConsoleOutput processRegionCommand(string $regionName, string $command)
 * @method static ConsoleOutput blink(string $msg)
 * @method static ConsoleOutput dumpArray(array $arr)
 * @method static ConsoleOutput debug(...$var)
 * @method static ConsoleOutput trace()
 * @method static ConsoleOutput traceRegion(string $regionTitle = 'trace', array $trace = null);
 * @method static string into1Line(string $message)
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
