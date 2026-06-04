<?php

namespace Infira\Console\Machine\Config;

class SshServerConfig extends MachineConfig
{
    public function getName(): string
    {
        return (string)$this->get('name', 'sshServer');
    }

    public function getUser(): string
    {
        return $this->get('user');
    }

    public function getPort(): ?int
    {
        return $this->get('port', null);
    }

}