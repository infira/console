<?php

namespace Infira\Console\Machine\Concerns;

use Infira\Console\Machine\MachineInstance;
use Infira\Console\Process;
use Wolo\File\FileHandler;

readonly class RSyncInstance
{
    public function __construct(public string|FileHandler $path,public MachineInstance $machine) {}


    public function rsync(string $src, string $destination, array $options = []): Process
    {
        $extraOptions = implode(' ', $options);

        return $this->process("rsync --timeout=0 -av --progress $extraOptions $src $destination");
    }
}