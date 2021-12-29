<?php

namespace Infira\console\helper;

class Output
{
	public static function into1Line(string $message): string
	{
		$ex       = preg_split('/\r\n|\r|\n/', trim($message));
		$newLines = [];
		array_map(function ($line) use (&$newLines)
		{
			$line = trim($line);
			if (strlen($line) > 0) {
				$newLines[] = $line;
			}
		}, $ex);
		
		return join("", $newLines);
	}
}