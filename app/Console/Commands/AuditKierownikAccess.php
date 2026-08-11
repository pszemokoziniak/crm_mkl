<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Audyt wpływu nowej definicji "budowy kierownika" (aktywne kierownictwo).
 *
 * 1) Budowy przypisane starymi kolumnami (kierownikBud_id / inzynier_id),
 *    których wskazany kierownik/inżynier NIE ma ŻADNEGO wpisu w kierownictwie
 *    (contact_work_dates z funkcją Kierownik/Inżynier) — te osoby stracą dostęp
 *    po przejściu na model oparty o contact_work_dates.
 * 2) Zestawienie: ilu użytkowników-kierowników i ile budów każdy z nich zobaczy
 *    po zmianie (kierownictwo obecne lub byłe).
 */
class AuditKierownikAccess extends Command
{
    protected $signature = 'kierownik:audit';

    protected $description = 'Audyt dostępu kierowników po przejściu na "aktywne kierownictwo" (stare kolumny vs contact_work_dates)';

    public function handle(): int
    {
        $this->info('=== 1) Budowy zależne od STARYCH kolumn (ryzyko utraty dostępu) ===');

        $legacyOrgs = Organization::query()
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNotNull('kierownikBud_id')->orWhereNotNull('inzynier_id');
            })
            ->get(['id', 'numerBud', 'nazwaBud', 'kierownikBud_id', 'inzynier_id']);

        $atRisk = [];

        foreach ($legacyOrgs as $org) {
            foreach (['kierownikBud_id', 'inzynier_id'] as $field) {
                $contactId = $org->{$field};
                if (!$contactId) {
                    continue;
                }

                $hasManagement = ContactWorkDate::query()
                    ->where('organization_id', $org->id)
                    ->where('contact_id', $contactId)
                    ->whereHas('contact', function ($c) {
                        $c->whereIn('funkcja_id', [Funkcja::KIEROWNIK, Funkcja::INZYNIER]);
                    })
                    ->exists();

                if (!$hasManagement) {
                    $contact = Contact::withTrashed()->find($contactId);
                    $atRisk[] = [
                        'budowa'  => trim(($org->numerBud ? $org->numerBud . '_' : '') . $org->nazwaBud),
                        'org_id'  => $org->id,
                        'pole'    => $field,
                        'osoba'   => $contact ? trim($contact->last_name . ' ' . $contact->first_name) : "#$contactId (brak kontaktu)",
                        'kontakt' => $contactId,
                    ];
                }
            }
        }

        if (empty($atRisk)) {
            $this->info('Brak — każda budowa ze starymi kolumnami ma też wpis w kierownictwie. Zmiana nikomu nie odbierze dostępu tą drogą.');
        } else {
            $this->warn(count($atRisk) . ' przypisań straci dostęp (stara kolumna bez wpisu w kierownictwie):');
            $this->table(['Budowa', 'Org ID', 'Pole', 'Osoba', 'Contact ID'], array_map('array_values', $atRisk));
        }

        $this->newLine();
        $this->info('=== 2) Co zobaczy każdy użytkownik-kierownik po zmianie ===');

        $kierownicy = User::query()->where('owner', Role::KIEROWNIK->value)->get();

        if ($kierownicy->isEmpty()) {
            $this->line('Brak użytkowników z rolą kierownik.');
            return self::SUCCESS;
        }

        $rows = [];
        foreach ($kierownicy as $user) {
            $contactId = $user->contactId();
            $count = $contactId
                ? Organization::query()->managedBy($contactId)->count()
                : 0;

            $rows[] = [
                'user'      => trim($user->first_name . ' ' . $user->last_name),
                'email'     => $user->email,
                'kontakt'   => $contactId ?? 'BRAK POWIĄZANIA',
                'budów_po'  => $count,
            ];
        }

        $this->table(['Użytkownik', 'Email', 'Contact ID', 'Budów po zmianie'], array_map('array_values', $rows));

        $noContact = collect($rows)->where('kontakt', 'BRAK POWIĄZANIA')->count();
        if ($noContact > 0) {
            $this->warn("$noContact kierowników nie ma powiązanego pracownika (contactId) — zobaczą 0 budów. Sprawdź powiązanie user↔contact.");
        }

        return self::SUCCESS;
    }
}
