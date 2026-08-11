<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContactPolicy
{
    use HandlesAuthorization;

    /**
     * Podgląd danych pracownika.
     * Admin/biuro — zawsze. Kierownik — tylko jeśli pracownik jest (lub był)
     * przypisany do którejś z budów, którymi kierownik AKTYWNIE kieruje.
     * Chroni PESEL/dowód/dane medyczne przed przeglądaniem obcych pracowników.
     */
    public function view(User $user, Contact $contact): bool
    {
        if ($user->isOffice()) {
            return true;
        }

        if ($user->isKierownik()) {
            $kierownikContactId = $user->contactId();

            if (!$kierownikContactId) {
                return false;
            }

            $myOrgIds = Organization::query()
                ->managedBy($kierownikContactId)
                ->pluck('id');

            if ($myOrgIds->isEmpty()) {
                return false;
            }

            return ContactWorkDate::query()
                ->where('contact_id', $contact->getKey())
                ->whereIn('organization_id', $myOrgIds)
                ->exists();
        }

        return false;
    }
}
