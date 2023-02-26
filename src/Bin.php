<?php

namespace Infira\Console;

use Infira\Console\Output\Console;
use Infira\Error\Handler;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Wolo\VarDumper;

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
                $output = new $type($input);
                $middlewareArguments[] = $output;
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

            if (!isset($output)) {
                $output = new Console($input);
            }

            $middleware(...$middlewareArguments);
            $app->setCatchExceptions(false);
            $app->setAutoExit(false);

            return $app->run($input, $output);
        }
        catch (\Throwable $e) {
            $stack = Handler::compile($e);
            $extra = array_filter($stack->toArray(), static function ($item, $key) {
                if (in_array($key, ['trace', 'server'])) {
                    return false;
                }

                return true;
            }, ARRAY_FILTER_USE_BOTH);

            /**
             * @var Console
             */
            if (isset($output)) {
                $output->error($e->getMessage());

                if ($extra) {
                    $output->dumpArray($extra);
                }
                if ($stack->trace) {
                    $output->miniRegion('trace', static fn() => $output->dumpTrace($stack->trace, self::$options['traceFormatter'] ?? null), null);
                }
            }
            else {
                VarDumper::console(['error' => $e->getMessage()]);
                if ($extra) {
                    VarDumper::console(['extra' => $extra]);
                }
                if ($stack->trace) {
                    $stack->trace = array_map(static function ($row) {
                        if (isset(self::$options['traceFormatter'])) {
                            return (self::$options['traceFormatter'])($row);
                        }

                        return $row;
                    }, $stack->trace);
                    VarDumper::console(['extra' => $stack->trace]);
                }
            }
            exit;
        }
    }
}
