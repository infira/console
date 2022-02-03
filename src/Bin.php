<?php

namespace Infira\console;

use Infira\Error\Handler;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Infira\Error\Error;
use Infira\Utils\Dir;

class Bin
{
	private static $basePath = '';
	/**
	 * @var ConsoleOutput
	 */
	private static $output;
	
	/**
	 * @var Handler
	 */
	private static $eh;
	
	public static function init(string $basePath)
	{
		self::$basePath = Dir::fixPath(realpath($basePath));
	}
	
	public static function getPath(string $path = null): string
	{
		$extra = $path ?: '';
		
		return Dir::fixPath(realpath(self::$basePath . $extra));
	}
	
	
	public static function run(string $appName, callable $middleware)
	{
		self::$eh = new Handler([
			"errorLevel"           => -1,//-1 means all erors, see https://www.php.net/manual/en/function.error-reporting.php
			"env"                  => "dev", //dev,stable (stable env does not display full errors erros
			"debugBacktraceOption" => DEBUG_BACKTRACE_IGNORE_ARGS,
		]);
		try {
			$input        = new ArgvInput();
			self::$output = new ConsoleOutput($input);
			$app          = new Application($appName);
			$middleware($app, self::$output);
			$app->setCatchExceptions(false);
			$app->run($input, self::$output);
			self::$output->info('command finished successfuly');
		}
		catch (Error $e) {
			self::handleThrowable($e);
		}
		catch (\Throwable $e) {
			self::handleThrowable($e);
		}
	}
	
	private static function handleThrowable(object $e)
	{
		if (!($e instanceof Error)) {
			$e = self::$eh->catch($e);
		}
		self::$output->error($e->getMessage());
		$extra = $e->getStack()->extra ?: [];
		if ($extra) {
			self::$output->region('Extra', function () use (&$extra)
			{
				self::$output->dumpArray($extra);
			});
		}
		$trace = $e->getStackTrace();
		if ($trace) {
			self::$output->traceRegion('error trace', $trace);
		}
	}
}
