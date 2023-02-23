<?php

namespace Infira\Console;

use Infira\Console\Output\ConsoleOutput;
use Infira\Error\Handler;

class Bin
{
    private static array $options = [];

    public static function init(
        array $options = [
            'traceFormatter' => null, //callable
            'errorHandlerDateFormat' => 'd.m.Y H:i:s'
        ]
    ): void {
        Handler::register([
            'dateFormat' => $options['errorHandlerDateFormat'] ?? 'd.m.Y H:i:s',

        ]);
        self::$options = $options;
    }

    public static function run(string $appName, callable $middleware): int
    {
        $ref = new \ReflectionFunction($middleware);
        $input = new \Symfony\Component\Console\Input\ArgvInput();
        Console::$output = new (self::$options['outputClass'] ?? ConsoleOutput::class)($input);
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

            $middleware($app, Console::$output, $input);
            $app->setCatchExceptions(false);
            $app->setAutoExit(false);

            return $app->run($input, Console::$output);
        }
        catch (\Throwable $e) {
            $stack = Handler::compile($e);
            Console::$output->error($e->getMessage());
            $extra = $stack->toArray();
            if ($extra) {
                $extra = array_filter($extra, static function ($item, $key) {
                    if (in_array($key, ['trace', 'server'])) {
                        return false;
                    }

                    return true;
                }, ARRAY_FILTER_USE_BOTH);
                Console::dumpArray($extra);
            }
            if ($stack->trace) {
                Console::miniRegion('trace', static function () use ($stack) {
                    Console::dumpTrace($stack->trace, self::$options['traceFormatter'] ?? null);
                });
            }
            exit;
        }
    }
}
