<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Zadanie;
use Illuminate\Auth\Access\HandlesAuthorization;

class ZadaniePolicy
{
    use HandlesAuthorization;

    /** Każdy zalogowany może zgłaszać i przeglądać listę (widzi tylko swoje). */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Zadanie $zadanie): bool
    {
        return $this->involved($user, $zadanie);
    }

    /** Edycja treści zgłoszenia: biuro, autor i osoba przypisana. */
    public function update(User $user, Zadanie $zadanie): bool
    {
        return $this->involved($user, $zadanie);
    }

    /** Zmiana statusu — te same osoby co edycja, ale bez wchodzenia w formularz. */
    public function updateStatus(User $user, Zadanie $zadanie): bool
    {
        return $this->involved($user, $zadanie);
    }

    public function comment(User $user, Zadanie $zadanie): bool
    {
        return $this->involved($user, $zadanie);
    }

    /** Archiwizacja i przywracanie: biuro albo autor zgłoszenia. */
    public function delete(User $user, Zadanie $zadanie): bool
    {
        return $user->isOffice() || (int) $zadanie->reporter_id === (int) $user->id;
    }

    public function restore(User $user, Zadanie $zadanie): bool
    {
        return $this->delete($user, $zadanie);
    }

    private function involved(User $user, Zadanie $zadanie): bool
    {
        return $user->isOffice()
            || (int) $zadanie->reporter_id === (int) $user->id
            || (int) $zadanie->assignee_id === (int) $user->id;
    }
}
