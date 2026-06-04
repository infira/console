<?php

namespace Infira\Console\Machine\Config;

class DockerImageConfig extends MachineConfig
{
    public function getName(): string
    {
        return (string)$this->get('name', 'docker');
    }

    public function getContainer(): string
    {
        return $this->get('container');
    }
}