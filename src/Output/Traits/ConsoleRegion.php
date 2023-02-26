<?php

namespace Infira\Console\Output\Traits;

use Infira\Console\Output\Console;
use Infira\Console\Output\ConsoleOutputWrapper;

/**
 * @mixin Console
 */
trait ConsoleRegion
{
    private ?int $defaultRegionMaxItems = null;
    /**
     * @var ConsoleOutputWrapper[]
     */
    private array $regions = [];

    /**
     * @param  string  $title
     * @param  callable  $process  - while region is open every output send to console will be caught
     * @param  int|null  $maxItems
     * @return $this
     */
    public function region(string $title, callable $process, int $maxItems = null): static
    {
        if (func_num_args() === 2) {
            $maxItems = $this->getRegionMaxItems();
        }

        $eq = str_repeat("=", 25);
        $this->addWrapper(
            "<comment>$eq".'['."<question> $title </question>".']'."$eq</comment>",
            $maxItems
        );
        $process();
        $this->popWrapper();

        return $this;
    }

    /**
     * @param  string  $title
     * @param  callable  $process  - while region is open every output send to console will be caught
     * @param  int|null  $maxItems
     * @return $this
     */
    public function miniRegion(string $title, callable $process, int $maxItems = null): static
    {
        if (func_num_args() === 2) {
            $maxItems = $this->getRegionMaxItems();
        }
        $eq = str_repeat('-', 25);
        $this->addWrapper(
            "<fg=magenta>$eq</>".'['." $title ".']'."<fg=magenta>$eq</>",
            $maxItems
        );
        $process();
        $this->popWrapper();

        return $this;
    }

    private function getRegionMaxItems(): ?int
    {
        return $this->defaultRegionMaxItems ?? null;
    }

    public function setRegionMaxItems(?int $items): static
    {
        $this->defaultRegionMaxItems = $items;

        return $this;
    }
}