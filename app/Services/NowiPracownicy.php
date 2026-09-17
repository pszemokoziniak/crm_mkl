<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Badania;
use App\Models\Bhp;
use App\Models\Contact;
use App\Models\Uprawnienia;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Nowo wprowadzeni pracownicy bez kompletu dokumentów na start: badania
 * lekarskie i szkolenie BHP obowiązkowo, uprawnienia opcjonalnie. Ekran
 * Kadry upomina się, dopóki obowiązkowe nie są wpisane i ważne; potem
 * pilnuje ich już zwykły raport terminów.
 */
class NowiPracownicy
{
    /** Po tylu dniach od wprowadzenia pracownik przestaje być "nowy" — brakami zajmuje się raport terminów. */
    public const DNI_NOWOSCI = 90;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function bezKompletu(?string $dzis = null): Collection
    {
        $dzis = $dzis ?? Carbon::today()->toDateString();
        $od = Carbon::parse($dzis)->subDays(self::DNI_NOWOSCI)->startOfDay();

        $nowi = Contact::query()
            ->where('created_at', '>=', $od)
            ->where(fn ($q) => $q->whereNull('status_zatrudnienia')->orWhere('status_zatrudnienia', '!=', Contact::STATUS_ZWOLNIONY))
            ->orderByDesc('created_at')
            ->get(['id', 'first_name', 'last_name', 'created_at']);

        if ($nowi->isEmpty()) {
            return collect();
        }

        $ids = $nowi->pluck('id')->all();
        $wazne = fn ($model) => $model::whereIn('contact_id', $ids)->where('end', '>=', $dzis)->pluck('contact_id')->unique()->flip();
        $badania = $wazne(Badania::class);
        $bhp = $wazne(Bhp::class);
        $uprawnienia = $wazne(Uprawnienia::class);

        return $nowi
            ->filter(fn (Contact $c) => ! isset($badania[$c->id]) || ! isset($bhp[$c->id]))
            ->map(fn (Contact $c) => [
                'id' => $c->id,
                'pracownik' => trim($c->last_name.' '.$c->first_name),
                'wprowadzony' => $c->created_at?->format('d.m.Y'),
                'dni_temu' => $c->created_at ? (int) $c->created_at->startOfDay()->diffInDays(Carbon::parse($dzis)) : null,
                'badania' => isset($badania[$c->id]),
                'bhp' => isset($bhp[$c->id]),
                'uprawnienia' => isset($uprawnienia[$c->id]),
            ])
            ->values();
    }
}
