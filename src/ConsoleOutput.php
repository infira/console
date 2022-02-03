<?php

namespace Infira\console;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Infira\Utils\Variable;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Infira\console\helper\Output;

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
	
	public function __construct($input, int $verbosity = OutputInterface::VERBOSITY_NORMAL, bool $decorated = null, OutputFormatterInterface $formatter = null)
	{
		parent::__construct($verbosity, $decorated, $formatter);
		$this->style = new SymfonyStyle($input, $this);
		
		$outputStyle = new OutputFormatterStyle('magenta');
		$this->getFormatter()->setStyle('title', $outputStyle);
	}
	
	public function info(string $msg): self
	{
		$this->say("<info>$msg</info>");
		
		return $this;
	}
	
	public function title(string $msg): self
	{
		$this->say("<title>$msg</title>");
		
		return $this;
	}
	
	public function comment(string $msg): self
	{
		$this->say("<comment>$msg</comment>");
		
		return $this;
	}
	
	public function msg(string $msg): self
	{
		$this->say($msg);
		
		return $this;
	}
	
	public function nl(): self
	{
		$gp                 = $this->globalPrefix;
		$this->globalPrefix = '';
		//$this->style->newLine(1);
		$this->writeln(" ");
		$this->globalPrefix = $gp;
		
		return $this;
	}
	
	public function cl(): self
	{
		$gp                 = $this->globalPrefix;
		$this->globalPrefix = '';
		$cursor             = new Cursor($this);
		$cursor->clearLine();
		$cursor->moveToColumn(1);
		$this->globalPrefix = $gp;
		
		return $this;
	}
	
	public function error(string $msg): self
	{
		$this->style->error($msg);
		
		return $this;
	}
	
	public function say(string $message): self
	{
		$ex = preg_split('/\r\n|\r|\n/', $message);
		array_map(function ($line, $key)
		{
			$line     = trim($line);
			$origLine = $line;
			if (strlen($line) > 0) {
				$line = str_replace('<nl/>', '', $line);
				$this->writeln($line);
				if (str_contains($origLine, '<nl/>')) {
					$this->nl();
				}
			}
		}, $ex, array_keys($ex));
		
		return $this;
	}
	
	public function sayWho(string $msg, string $saysWho): self
	{
		$msg = Output::into1Line($msg);
		if (!$msg) {
			return $this;
		}
		
		$msg = trim($msg);
		$msg = $msg ? " $msg" : '';
		//$title = $this->sayTitle ? "<title> $this->sayTitle </title>" : '';
		$msg = "<fg=black;bg=bright-yellow>$saysWho: </>$msg";
		$this->say($msg);
		
		return $this;
	}
	
	public function write($messages, bool $newline = false, int $options = self::OUTPUT_NORMAL): self
	{
		if (!is_iterable($messages)) {
			$messages = [$messages];
		}
		foreach ($messages as $k => $message) {
			$messages[$k] = $this->globalPrefix ? $this->globalPrefix . $message : $message;
		}
		parent::write($messages, $newline, $options);
		
		return $this;
	}
	
	public function region(string $region, callable $regionProcess): self
	{
		$msg = str_repeat("=", 25);
		$msg .= "[<question> $region </question>]";
		$msg .= str_repeat("=", 25);
		$this->comment($msg);
		$this->nl();
		$regionProcess();
		$this->nl();
		$this->comment($msg);
		
		return $this;
	}
	
	public function blink(string $msg): self
	{
		$outputStyle = new OutputFormatterStyle('red', '#ff0', ['bold', 'blink']);
		$this->getFormatter()->setStyle('fire', $outputStyle);
		$this->writeln("<fire>$msg</>");
		
		return $this;
	}
	
	public function dumpArray(array $arr): self
	{
		$this->writeln(Variable::dump($arr));
		
		return $this;
	}
	
	public function debug(...$var): self
	{
		foreach ($var as $v) {
			$this->nl->writeln(Variable::dump($v));
		}
		
		return $this;
	}
	
	public function trace(): self
	{
		return $this->dumpArray(debug_backtrace());
	}
	
	public function traceRegion(string $regionTitle = 'trace', array $trace = null)
	{
		$trace = $trace ?: debug_backtrace();
		self::nl()->region($regionTitle, function () use ($trace)
		{
			foreach ($trace as $key => $row) {
				$key++;
				$row['file'] = $row['file'] ?? '';
				$row['line'] = $row['line'] ?? '';
				self::writeln($key . ') in file <info>' . $row['file'] . ' </info> on line ' . $row['line']);
			}
		});
	}
}