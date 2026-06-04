<?php

namespace Infira\Console;

class Utils
{
    public static function eachLine(string|array $message, callable $callback): void
    {
        if (is_array($message)) {
            $message = implode("\n", $message);
        }
        foreach (preg_split('/\r\n|\r|\n/', trim($message)) as $line) {
            $callback(trim($line));
        }
    }

    public static function renderString(mixed $template, array $vars, string|array $syntax = '{}'): string
    {
        $map = [];
        foreach ($vars as $name => $value) {
            foreach ((array)$syntax as $singleSyntax) {
                [$start, $end] = mb_str_split($singleSyntax, 1);
                $map[$start.$name.$end] = $value;
            }
        }

        return strtr($template, $map);
    }
}