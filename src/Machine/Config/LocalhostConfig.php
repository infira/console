<?php

namespace Infira\Console\Machine\Config;

class LocalhostConfig extends MachineConfig
{
    public function getName(): string
    {
        return (string)$this->get('name', 'localhost');
    }
}