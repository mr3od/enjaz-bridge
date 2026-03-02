<?php

namespace App\Support\Tenancy;

use App\Models\Agency;

class TenantContext
{
    private ?Agency $agency = null;

    public function setAgency(Agency $agency): void
    {
        $this->agency = $agency;
    }

    public function agency(): ?Agency
    {
        return $this->agency;
    }

    public function agencyId(): ?string
    {
        return $this->agency?->id;
    }

    public function clear(): void
    {
        $this->agency = null;
    }
}
