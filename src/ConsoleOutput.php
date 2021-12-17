<?php

namespace Infira\console;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Infira\Utils\Variable;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class ConsoleOutput extends \Symfony\Component\Console\Output\ConsoleOutput
{
	/**
	 * @var \Symfony\Component\Console\Style\SymfonyStyle
	 */
	private $style;
	
	/**
	 * @var string
	 */
	private $globalPrefix = '';
	
	private $titleSection = '';
	
	public function __construct($input, int $verbosity = OutputInterface::VERBOSITY_NORMAL, bool $decorated = null, OutputFormatterInterface $formatter = null)
	{
		\Symfony\Component\Console\Output\ConsoleOutput::__construct($verbosity, $decorated, $formatter);
		$this->style = new SymfonyStyle($input, $this);
		
		$outputStyle = new OutputFormatterStyle('magenta');
		$this->getFormatter()->setStyle('title', $outputStyle);
	}
	
	public function info(string $msg): ConsoleOutput
	{
		$this->say("<info>$msg</info>");
		
		return $this;
	}
	
	public function title(string $msg): ConsoleOutput
	{
		$this->say("<title>$msg</title>");
		
		return $this;
	}
	
	public function comment(string $msg): ConsoleOutput
	{
		$this->say("<comment>$msg</comment>");
		
		return $this;
	}
	
	public function msg(string $msg): ConsoleOutput
	{
		$this->say($msg);
		
		return $this;
	}
	
	public function nl(): ConsoleOutput
	{
		$gp                 = $this->globalPrefix;
		$this->globalPrefix = '';
		//$this->style->newLine(1);
		$this->writeln(" ");
		$this->globalPrefix = $gp;
		
		return $this;
	}
	
	public function cl(): ConsoleOutput
	{
		$gp                 = $this->globalPrefix;
		$this->globalPrefix = '';
		$cursor             = new Cursor($this);
		$cursor->clearLine();
		$cursor->moveToColumn(1);
		$this->globalPrefix = $gp;
		
		return $this;
	}
	
	public function error(string $msg): ConsoleOutput
	{
		$this->style->error($msg);
		
		return $this;
	}
	
	public function say(string $message)
	{
		$ex = preg_split('/\r\n|\r|\n/', $message);
		array_map(function ($line, $key)
		{
			$line     = trim($line);
			$origLine = $line;
			if (strlen($line) > 0)
			{
				$line = str_replace('<nl/>', '', $line);
				$this->writeln($line);
				if (str_contains($origLine, '<nl/>'))
				{
					$this->nl();
				}
			}
		}, $ex, array_keys($ex));
	}
	
	public function sayWho(string $msg, string $saysWho)
	{
		$msg = $this->into1Line($msg);
		if (!$msg)
		{
			return $this;
		}
		
		$msg = trim($msg);
		$msg = $msg ? " $msg" : '';
		//$title = $this->sayTitle ? "<title> $this->sayTitle </title>" : '';
		$msg = "<fg=black;bg=bright-yellow>$saysWho: </>$msg";
		$this->say($msg);
		
		return $this;
	}
	
	public function write($messages, bool $newline = false, int $options = self::OUTPUT_NORMAL): ConsoleOutput
	{
		if (!is_iterable($messages))
		{
			$messages = [$messages];
		}
		foreach ($messages as $k => $message)
		{
			$messages[$k] = $this->globalPrefix ? $this->globalPrefix . $message : $message;
		}
		parent::write($messages, $newline, $options);
		
		return $this;
	}
	
	public function region(string $region, callable $regionProcess)
	{
		$msg = str_repeat("=", 25);
		$msg .= "[<question> $region </question>]";
		$msg .= str_repeat("=", 25);
		$this->comment($msg);
		$this->nl();
		$regionProcess();
		$this->nl();
		$this->comment($msg);
	}
	
	public function titleSection(string $title, callable $between)
	{
		$this->sayTitle = $title;
		$between();
		$this->sayTitle = trim(substr($this->sayTitle, 0, strlen($title)));
	}
	
	public function dumpArray(array $arr)
	{
		$dump = Variable::dump($arr);
		$this->writeln($dump);
	}
	
	public function into1Line(string $message): string
	{
		$ex       = preg_split('/\r\n|\r|\n/', trim($message));
		$newLines = [];
		array_map(function ($line) use (&$newLines)
		{
			$line = trim($line);
			if (strlen($line) > 0)
			{
				$newLines[] = $line;
			}
		}, $ex);
		
		return join("", $newLines);
	}
}