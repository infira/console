<?php

namespace Infira\Console;

use Infira\Console\Output\ConsoleOutput;
use Infira\Error\Handler;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

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
        $app = null;
        $middlewareArguments = [];
        foreach ($ref->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (!$type) {
                throw new \RuntimeException("parameter('".$parameter->getName()." type not defined");
            }
            $type = $type->getName();
            if (is_a($type, InputInterface::class, true)) {
                $input = new $type();
                $middlewareArguments[] = $input;
            }
            elseif (is_a($type, Application::class, true)) {
                $app = new $type($appName);
                $middlewareArguments[] = $app;
            }
            elseif (is_a($type, ConsoleOutputInterface::class, true)) {
                if (!isset($input)) {
                    $input = new ArgvInput();
                }
                Console::$output = new $type($input);
                $middlewareArguments[] = Console::$output;
            }
            else {
                throw new \RuntimeException("Unknown type('$type')");
            }
        }
        try {
            if (!$app) {
                $app = new Application($appName);
            }

            if (!isset($input)) {
                $input = new ArgvInput();
            }

            if (!isset(Console::$output)) {
                Console::$output = new ConsoleOutput($input);
            }

            $middleware(...$middlewareArguments);
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
