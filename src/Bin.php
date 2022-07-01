<?php

namespace Infira\console;

use Infira\Error\Handler;
use Wolo\File\Path;

class Bin
{
	private static $basePath = '';
	
	/**
	 * @var Handler
	 */
	private static $eh;
	
	public static function init(string $basePath)
	{
		self::$basePath = Path::slash(realpath($basePath));
		Handler::register();
	}
	
	public static function getPath(string $path = null): string
	{
		return Path::join(realpath(self::$basePath), $path);
	}
	
	public static function run(string $appName, callable $middleware)
	{
		$ref             = new \ReflectionFunction($middleware);
		$input           = new \Symfony\Component\Console\Input\ArgvInput();
		Console::$output = new ConsoleOutput($input);
		try {
			$app = null;
			
			foreach ($ref->getParameters() as $parameter) {
				$type = $parameter->getType();
				if (!$type) {
					continue;
				}
				$type = $type->getName();
				if ($type instanceof \Symfony\Component\Console\Input\InputInterface) {
					$input = new $type();
				}
				elseif ($type instanceof \Symfony\Component\Console\Application) {
					$app = new $type($appName);
				}
				elseif ($type instanceof \Symfony\Component\Console\Output\ConsoleOutput) {
					Console::$output = new $type($input);
				}
			}
			if (!$app) {
				$app = new \Symfony\Component\Console\Application($appName);
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
