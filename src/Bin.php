<?php

namespace Infira\console;

use Infira\Error\Handler;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Infira\Error\Error;
use Wolo\File\Path;

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
		self::$basePath = Path::slash(realpath($basePath));
	}
	
	public static function getPath(string $path = null): string
	{
		return Path::join(realpath(self::$basePath), $path);
	}
	
	public static function error(string $msg)
	{
		self::$output->info($msg);
	}
	
	public static function run(string $appName, callable $middleware)
	{
		Handler::register();
		try {
			$input        = new ArgvInput();
			self::$output = new ConsoleOutput($input);
			$app          = new Application($appName);
			$middleware($app, self::$output);
			$app->setCatchExceptions(false);
			$app->run($input, self::$output);
			self::$output->info('command finished successfuly');
		}
		catch (\Throwable $e) {
			$stack = Handler::compile($e);
			self::$output->error($e->getMessage());
			$extra = $stack->toArray();
			if ($extra) {
				self::$output->region('Extra', function () use (&$extra)
				{
					self::$output->dumpArray($extra);
				});
			}
			$trace = $stack->trace;
			if ($trace) {
				self::$output->traceRegion('error trace', $trace);
			}
			
		}
	}
}
