<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganizationPolicy
{
    use HandlesAuthorization;

    /**
     * Podgląd danych budowy.
     * Admin/biuro — zawsze. Kierownik — tylko jeśli jest lub był
     * w kierownictwie tej budowy (patrz Organization::scopeManagedBy).
     */
    public function view(User $user, Organization $organization): bool
    {
        if ($user->isOffice()) {
            return true;
        }

        if ($user->isKierownik()) {
            $contactId = $user->contactId();

            if (!$contactId) {
                return false;
            }

            return Organization::query()
                ->whereKey($organization->getKey())
                ->managedBy($contactId)
                ->exists();
        }

        return false;
    }
}
