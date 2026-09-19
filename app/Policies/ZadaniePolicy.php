<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Zadanie;
use Illuminate\Auth\Access\HandlesAuthorization;

class ZadaniePolicy
{
    use HandlesAuthorization;

    /** Każdy zalogowany może zgłaszać i przeglądać listę wszystkich zadań. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /** Podgląd zgłoszenia ma każdy zalogowany — zadania są wspólne (19.09.2026). */
    public function view(User $user, Zadanie $zadanie): bool
    {
        return true;
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
