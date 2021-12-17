<?php

namespace Infira\console\helper;

use Symfony\Component\Yaml\Yaml;
use stdClass;
use Exception;

class Config
{
	protected $config = [];
	
	public function __construct(string $yamlFile)
	{
		$this->config = Yaml::parseFile($yamlFile);
	}
	
	protected function getPathArr(string $configPath): array
	{
		return explode('.', $configPath);;
	}
	
	public function get(string $configPath)
	{
		if (!$this->exists($configPath))
		{
			throw new Exception("config path $configPath does not exist");
		}
		$to = &$this->config;
		foreach ($this->getPathArr($configPath) as $p)
		{
			$to = &$to[$p];
		}
		
		return $to;
	}
	
	public function exists(string $configPath): bool
	{
		$to = &$this->config;
		foreach ($this->getPathArr($configPath) as $p)
		{
			if (!array_key_exists($p, $to))
			{
				return false;
			}
		}
		
		return true;
	}
	
	protected function set(string $configPath, $value)
	{
		$to    = &$this->config;
		$lastP = null;
		foreach ($this->getPathArr($configPath) as $p)
		{
			if (!array_key_exists($p, $to))
			{
				$to[$p] = new stdClass();
			}
			$to    = &$to[$p];
			$lastP = $p;
		}
		$to[$lastP] = $value;
	}
	
	protected function add(string $configPath, $value)
	{
		$to    = &$this->config;
		$lastP = null;
		foreach ($this->getPathArr($configPath) as $p)
		{
			if (!property_exists($to, $p))
			{
				$to[$p] = new stdClass();
			}
			$to    = &$to[$p];
			$lastP = $p;
		}
		$to[$lastP][] = $value;
	}
}