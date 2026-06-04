<?php

namespace Infira\Console\Machine\Config;

use Infira\Console\Config;
use Infira\Console\Exceptions\MissingMachineConfigException;

class MachineConfig extends Config
{
    protected static string $missingConfigException = MissingMachineConfigException::class;

    public function getName(): string
    {
        return (string)$this->get('name', 'machine');
    }

    public function getHost(): string
    {
        return $this->get('host');
    }

    public function getTempPath(): string
    {
        return $this->get('tempPath');
    }

    //region sql
    public function getSqlRootUser(): string
    {
        return $this->get('sql.rootUser', 'root');
    }

    public function getSqlRootPassword(): string
    {
        return $this->get('sql.rootPassword');
    }

    public function getSqlCommand(): string
    {
        return $this->get('sql.command', 'mysql');
    }
    //endregion sql
}