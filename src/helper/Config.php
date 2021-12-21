<?php

namespace Infira\console\helper;

use Symfony\Component\Yaml\Yaml;
use Exception;

class Config
{
	protected $config = [];
	
	public function __construct(string $yamlFile)
	{
		$this->mergeConfig($yamlFile);
	}
	
	public function mergeConfig(string $yamlFile)
	{
		if (!file_exists($yamlFile))
		{
			throw new Exception("yaml config('$yamlFile') does not exist");
		}
		$this->config = array_merge($this->config, Yaml::parseFile($yamlFile));
	}
	
	protected function getPathArr(string $configPath): array
	{
		return explode('.', $configPath);;
	}
	
	public function getAll(): array
	{
		return $this->config;
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
	
	public function set(string $configPath, $value)
	{
		$to      = &$this->config;
		$pathArr = $this->getPathArr($configPath);
		$lastKey = array_key_last($pathArr);
		foreach ($pathArr as $key => $p)
		{
			if (!array_key_exists($p, $to))
			{
				$to[$p] = [];
			}
			if ($key == $lastKey)
			{
				$to[$p] = $value;
				break;
			}
			$to = &$to[$p];
		}
	}
	
	public function add(string $configPath, $value)
	{
		$to      = &$this->config;
		$pathArr = $this->getPathArr($configPath);
		$lastKey = array_key_last($pathArr);
		foreach ($pathArr as $key => $p)
		{
			if (!array_key_exists($p, $to))
			{
				$to[$p] = [];
			}
			if ($key == $lastKey)
			{
				$to[$p][] = $value;
				break;
			}
			$to = &$to[$p];
		}
	}
}