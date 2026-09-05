<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactWorkDate;

/**
 * Jedna reguła kolizji terminów dla obu dróg przypisania pracownika:
 * z karty pracownika i z zakładki budowy.
 *
 * Dwie różne sprawy, dotąd mylone:
 * 1. ten sam pracownik dwa razy na TEJ SAMEJ budowie w tym samym czasie —
 *    zawsze pomyłka, także dla kierownictwa,
 * 2. pracownik w tym czasie na INNEJ budowie — dla kierownictwa dozwolone
 *    (koordynator obsługuje kilka budów), dla pozostałych blokowane.
 */
class KolizjaPobytu
{
    /**
     * Komunikat do pokazania, gdy przypisania nie wolno zapisać.
     * null oznacza, że można zapisywać.
     */
    public function komunikat(Contact $contact, int $organizationId, string $start, ?string $end): ?string
    {
        $kolidujace = ContactWorkDate::with('organization')
            ->where('contact_id', $contact->id)
            ->where('start', '<=', $end ?: $start)
            ->where(function ($query) use ($start) {
                $query->whereNull('end')->orWhere('end', '>=', $start);
            })
            ->get();

        $taSamaBudowa = $kolidujace->firstWhere('organization_id', $organizationId);

        if ($taSamaBudowa) {
            return 'Ten pracownik jest już przypisany do tej budowy w terminie '
                .$this->termin($taSamaBudowa).'. Nie trzeba dodawać go drugi raz — '
                .'żeby zmienić daty, popraw istniejący pobyt.';
        }

        $inna = $kolidujace->first();

        if (! $inna || optional($contact->funkcja)->kierownictwo) {
            return null;
        }

        $nazwa = optional($inna->organization)->nazwaBud ?? 'inna budowa (już usunięta)';

        return 'Pracownik jest w tym terminie na budowie: '.$nazwa.' ('.$this->termin($inna).'). '
            .'Najpierw skróć tamten pobyt.';
    }

    private function termin(ContactWorkDate $pobyt): string
    {
        return $pobyt->start.' – '.($pobyt->end ?: 'bez końca');
    }
}
