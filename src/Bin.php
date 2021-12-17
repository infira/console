<?php

class Bin
{

}


use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Infira\Error\Handler;
use Infira\pmg\Pmg;

if (file_exists(__DIR__ . '/../../../autoload.php'))
{
	require __DIR__ . '/../../../autoload.php';
}
else
{
	require __DIR__ . '/../vendor/autoload.php';
}

function br2nl(string $str)
{
	return str_replace(['<br />', '<br>', '< br>'], "\n", $str);
}

define("PMG_PATH", realpath(__DIR__ . '/../') . '/');

$out     = new ConsoleOutput();
$Handler = new Handler([
	"errorLevel"           => -1,//-1 means all erors, see https://www.php.net/manual/en/function.error-reporting.php
	"env"                  => "dev", //dev,stable (stable env does not display full errors erros
	"debugBacktraceOption" => DEBUG_BACKTRACE_IGNORE_ARGS,
]);


try
{
	$pmg = new Pmg();
	$app = new Application('poesis-mg');
	$app->add($pmg);
	$app->setDefaultCommand($pmg->getName());
	$app->setCatchExceptions(false);
	$input = new ArgvInput();
	$app->run($input, new \Infira\pmg\helper\ConsoleOutput($input));
}
catch (\Infira\Error\Error $e)
{
	debug($e->getStack()->extra);
	$out->writeln("General error: <error>" . br2nl($e->getMessage()) . "</error>");
	$out->writeln('<info>Trace</info>');
	foreach ($e->getStackTrace() as $key => $row)
	{
		$key++;
		$row['file'] = $row['file'] ?? '';
		$row['line'] = $row['line'] ?? '';
		$out->writeln($key . ') in file <info>' . $row['file'] . ' on line <info>' . $row['line'] . '</info>');
	}
}
catch (Throwable $e)
{
	$ie = $Handler->catch($e);
	debug($ie->getStack()->extra);
	$out->writeln("General error: <error>" . br2nl($ie->getMessage()) . "</error>");
	$out->writeln('<info>Trace</info>');
	foreach ($ie->getStackTrace() as $key => $row)
	{
		$key++;
		$row['file'] = $row['file'] ?? '';
		$row['line'] = $row['line'] ?? '';
		$out->writeln($key . ') in file <info>' . $row['file'] . ' on line <info>' . $row['line'] . '</info>');
	}
}