<?php

namespace Infira\console;

use Infira\Error\Handler;
use Infira\Error\Error;
use Wolo\File\Path;

class Bin
{
	private static $basePath = '';
	/**
	 * @var ConsoleOutput
	 */
	private static $output = null;
	
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
	
	public static function run(string $appName, callable $middleware)
	{
		Handler::register();
		try {
			$ref   = new \ReflectionFunction($middleware);
			$input = null;
			$app   = null;
			
			foreach ($ref->getParameters() as $parameter) {
				$type = $parameter->getType()->getName();
				if ($type instanceof \Symfony\Component\Console\Input\InputInterface) {
					$input = new $type();
				}
			}
			if (!$input) {
				$input = new \Symfony\Component\Console\Input\ArgvInput();
			}
			
			foreach ($ref->getParameters() as $parameter) {
				$type = $parameter->getType()->getName();
				if ($type instanceof \Symfony\Component\Console\Application) {
					$app = new $type($appName);
				}
				elseif ($type instanceof \Symfony\Component\Console\Output\ConsoleOutput) {
					Console::$output = new $type($input);
				}
			}
			if (!$app) {
				$app = new \Symfony\Component\Console\Application($appName);
			}
			if (!Console::$output) {
				Console::$output = new ConsoleOutput($input);
			}
			
			
			$middleware($app, Console::$output);
			$app->setCatchExceptions(false);
			$app->run($input, Console::$output);
			Console::info('command finished successfuly');
		}
		catch (\Throwable $e) {
			$stack = Handler::compile($e);
			Console::$output->error($e->getMessage());
			$extra = $stack->toArray();
			if ($extra) {
				Console::region('Extra', function () use (&$extra)
				{
					$extra = array_filter($extra, function ($item, $key)
					{
						if (in_array($key, ['trace', 'server'])) {
							return false;
						}
						
						return true;
					}, ARRAY_FILTER_USE_BOTH);
					Console::dumpArray($extra);
				});
			}
			$trace = $stack->trace;
			if ($trace) {
				Console::traceRegion('error trace', $trace);
			}
			
		}
	}
}
