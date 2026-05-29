<?php

namespace App\Listeners;

use Stancl\Tenancy\Events\TenantCreated;

class CreateTenantDomain
{
    public function handle(TenantCreated $event)
    {
        $tenant = $event->tenant;

        // Define o sufixo do domínio (ex: .localhost ou .amemiya.test)
        // Em produção, isso viria de uma configuração central.
        $domainSuffix = config('app.url_suffix', 'localhost');

        $domain = $tenant->slug.'.'.$domainSuffix;

        $tenant->domains()->create([
            'domain' => $domain,
        ]);
    }
}
