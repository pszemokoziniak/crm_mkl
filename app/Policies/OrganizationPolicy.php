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
     * Wejście/edycja budowy.
     * Admin/biuro — zawsze. Kierownik — tylko jeśli jest AKTYWNIE
     * w kierownictwie tej budowy DZIŚ (patrz Organization::scopeActivelyManagedBy).
     * Zamknięte budowy kierownik widzi na liście, ale nie może w nie wejść.
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
                ->activelyManagedBy($contactId)
                ->exists();
        }

        return false;
    }
}
