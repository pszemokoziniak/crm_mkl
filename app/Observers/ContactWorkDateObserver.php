<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ContactWorkDate;
use App\Services\RejestrZmianKadrowych;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Jedno miejsce, w którym łapiemy zmiany pobytów — niezależnie od tego, czy
 * poszły z karty pracownika, z zakładki budowy, czy z kierownictwa.
 */
class ContactWorkDateObserver
{
    public function __construct(private RejestrZmianKadrowych $rejestr)
    {
    }

    public function created(ContactWorkDate $pobyt): void
    {
        $this->bezpiecznie(fn () => $this->rejestr->nowyPobyt($pobyt), $pobyt);
    }

    public function updated(ContactWorkDate $pobyt): void
    {
        if (! $pobyt->wasChanged(['start', 'end', 'organization_id'])) {
            return;
        }

        $poprzednie = [
            'start' => $pobyt->getOriginal('start'),
            'end' => $pobyt->getOriginal('end'),
        ];

        $this->bezpiecznie(fn () => $this->rejestr->zmienionyPobyt($pobyt, $poprzednie), $pobyt);
    }

    public function deleted(ContactWorkDate $pobyt): void
    {
        $this->bezpiecznie(fn () => $this->rejestr->usunietyPobyt($pobyt), $pobyt);
    }

    /**
     * Rejestr jest sprawą wtórną wobec grafiku: gdyby zapis do niego padł,
     * przypisanie pracownika i tak ma się udać. Błąd ląduje w logu.
     */
    private function bezpiecznie(callable $akcja, ContactWorkDate $pobyt): void
    {
        // Bez zalogowanego użytkownika (seedy, konsola, import) nie ma czego
        // zgłaszać kadrom — rejestr opisuje decyzje ludzi, nie migracje danych.
        if (! Auth::check()) {
            return;
        }

        try {
            $akcja();
        } catch (\Throwable $e) {
            Log::error('Nie udało się zapisać zmiany kadrowej: '.$e->getMessage(), [
                'contact_work_date_id' => $pobyt->id,
            ]);
        }
    }
}
