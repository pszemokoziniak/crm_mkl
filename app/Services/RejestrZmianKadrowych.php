<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ContactWorkDate;
use App\Models\User;
use App\Models\ZmianaKadrowa;
use App\Notifications\ZmianaKadrowaNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Zapisuje zmiany pobytów na budowach do rejestru dla kadr.
 *
 * Dwie rzeczy, które robi poza samym zapisem:
 * 1. skrócenie pobytu na budowie A i dodanie pobytu na budowie B dla tej samej
 *    osoby to jedno "przeniesienie", a nie dwa osobne wpisy,
 * 2. zmiany wprowadzone jednym ciągiem przez tę samą osobę trafiają do wspólnej
 *    paczki, żeby kadry zamykały przeniesienie ekipy jednym kliknięciem.
 */
class RejestrZmianKadrowych
{
    /** Okno, w którym zmiany sklejają się w jedną paczkę (minuty). */
    private const OKNO_PACZKI = 30;

    public function nowyPobyt(ContactWorkDate $pobyt): void
    {
        // Jeśli chwilę wcześniej skrócono/zdjęto pobyt tej osoby — to przeniesienie.
        $poprzednia = $this->swiezaZmianaTejOsoby($pobyt->contact_id, [
            ZmianaKadrowa::TYP_SKROCENIE,
            ZmianaKadrowa::TYP_USUNIECIE,
        ]);

        if ($poprzednia) {
            $poprzednia->update([
                'typ' => ZmianaKadrowa::TYP_PRZENIESIENIE,
                'organization_to_id' => $pobyt->organization_id,
                'new_start' => $pobyt->start,
                'new_end' => $pobyt->end,
            ]);

            return;
        }

        $this->zapisz($pobyt->contact_id, ZmianaKadrowa::TYP_NOWY, [
            'organization_to_id' => $pobyt->organization_id,
            'new_start' => $pobyt->start,
            'new_end' => $pobyt->end,
        ]);
    }

    public function zmienionyPobyt(ContactWorkDate $pobyt, array $poprzednie): void
    {
        $staryStart = $poprzednie['start'] ?? null;
        $staryKoniec = $poprzednie['end'] ?? null;

        if ((string) $staryStart === (string) $pobyt->start && (string) $staryKoniec === (string) $pobyt->end) {
            return;
        }

        $typ = ZmianaKadrowa::TYP_ZMIANA_TERMINU;

        if ((string) $staryStart === (string) $pobyt->start && $staryKoniec && $pobyt->end) {
            $typ = $pobyt->end < $staryKoniec
                ? ZmianaKadrowa::TYP_SKROCENIE
                : ZmianaKadrowa::TYP_WYDLUZENIE;
        }

        // Tylko budowa "z" — pole "do" zostaje puste, bo to ono decyduje,
        // czy wpis da się jeszcze skleić z nowym pobytem w przeniesienie.
        $this->zapisz($pobyt->contact_id, $typ, [
            'organization_from_id' => $pobyt->organization_id,
            'old_start' => $staryStart,
            'old_end' => $staryKoniec,
            'new_start' => $pobyt->start,
            'new_end' => $pobyt->end,
        ]);
    }

    public function usunietyPobyt(ContactWorkDate $pobyt): void
    {
        $this->zapisz($pobyt->contact_id, ZmianaKadrowa::TYP_USUNIECIE, [
            'organization_from_id' => $pobyt->organization_id,
            'old_start' => $pobyt->start,
            'old_end' => $pobyt->end,
        ]);
    }

    private function zapisz(int $contactId, string $typ, array $dane): void
    {
        $paczka = $this->paczkaDlaBiezacejSerii();
        $pierwszaWPaczce = ! ZmianaKadrowa::where('paczka', $paczka)->exists();

        $zmiana = ZmianaKadrowa::create(array_merge([
            'contact_id' => $contactId,
            'typ' => $typ,
            'paczka' => $paczka,
            'status' => ZmianaKadrowa::STATUS_NOWA,
            'changed_by' => Auth::id(),
        ], $dane));

        // Dzwonek dzwoni raz na paczkę, a nie raz na osobę.
        if ($pierwszaWPaczce) {
            $this->powiadomKadry($zmiana);
        }
    }

    /**
     * Klucz paczki: ten sam autor i ten sam ciąg pracy. Świadomie nie
     * uwzględniamy budów — przenosząc ekipę, montaż robi serię zmian,
     * które dla kadr są jedną sprawą.
     */
    private function paczkaDlaBiezacejSerii(): string
    {
        $ostatnia = ZmianaKadrowa::where('changed_by', Auth::id())
            ->where('created_at', '>=', Carbon::now()->subMinutes(self::OKNO_PACZKI))
            ->latest('id')
            ->first();

        return $ostatnia->paczka ?? (string) Str::uuid();
    }

    /** Wpis o zmianie tej samej osoby sprzed chwili — kandydat na przeniesienie. */
    private function swiezaZmianaTejOsoby(int $contactId, array $typy): ?ZmianaKadrowa
    {
        return ZmianaKadrowa::where('contact_id', $contactId)
            ->whereIn('typ', $typy)
            ->where('changed_by', Auth::id())
            ->where('status', ZmianaKadrowa::STATUS_NOWA)
            ->where('created_at', '>=', Carbon::now()->subMinutes(self::OKNO_PACZKI))
            ->whereNull('organization_to_id')
            ->latest('id')
            ->first();
    }

    private function powiadomKadry(ZmianaKadrowa $zmiana): void
    {
        try {
            $kadry = User::where('owner', 2)
                ->where('active', true)
                ->where('id', '!=', Auth::id())
                ->get();

            if ($kadry->isEmpty()) {
                return;
            }

            Notification::send($kadry, new ZmianaKadrowaNotification($zmiana));
        } catch (\Throwable $e) {
            Log::warning('Nie udało się powiadomić kadr o zmianie pobytu: '.$e->getMessage(), [
                'zmiana_id' => $zmiana->id,
            ]);
        }
    }
}
