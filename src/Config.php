<?php

namespace Infira\Console;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Infira\Console\Exceptions\MissingConfigException;
use Wolo\File\Path;

class Config extends Repository implements Arrayable
{
    protected array $defaultConfig;
    protected static string $missingConfigException = MissingConfigException::class;

    public function __construct(array $config = [])
    {
        if (isset($this->defaultConfig)) {
            $config = [
                ...$this->defaultConfig,
                ...$config
            ];
        }
        parent::__construct($config);
    }

    public function extend(array|self $data): static
    {
        return new static([
            ...$this->toArray(),
            ...($data instanceof self ? $data : new static($data))->toArray(),
        ]);
    }

    /**
     * Get the specified configuration value.
     *
     * @param array|string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, mixed $default = null): mixed
    {
        if (!$this->has($key) && func_num_args() === 1) {
            throw (new self::$missingConfigException)("config('".static::class."') key('$key') does not exist.");
        }

        return parent::get($key, $default);
    }

    /**
     * get value as class
     * @template TClass of object
     * @param string $key
     * @param class-string<TClass> $class
     * @param mixed $defaultPassArg
     * @return TClass
     */
    public function getAs(string $key, string $class, mixed $defaultPassArg = []): object
    {
        return new $class($this->get($key, $defaultPassArg));
    }

    public function getAsCollection(string $key): Collection
    {
        return new Collection((array)$this->get($key, []));
    }

    public function getPath(string $key, string ...$part): string
    {
        return Path::join($this->get($key), ...$part);
    }

    public function toArray(): array
    {
        return $this->items;
    }
}