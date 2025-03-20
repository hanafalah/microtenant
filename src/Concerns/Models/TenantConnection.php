<?php

declare(strict_types=1);

namespace Zahzah\MicroTenant\Concerns\Models;

use Stancl\Tenancy\Database\Concerns\TenantConnection as ConcernsTenantConnection;

trait TenantConnection
{
    use ConcernsTenantConnection;

    public function initializeTenantConnection(){
        $this->connection = 'tenant';
    }
}
