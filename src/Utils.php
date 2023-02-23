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
            $callback($line);
        }
    }
}