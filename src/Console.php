<?php

namespace Infira\console;

use Symfony\Component\Console\Output\OutputInterface;
use Infira\Error\Error;
use Infira\Error\AlertException;

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
	public static $output;
	
	public static function __callStatic(string $name, array $arguments)
	{
		return self::$output->$name(...$arguments);
	}
	
	public static function error(string $msg, mixed $data = null): void
	{
		Error::trigger($msg, $data);
	}
}
