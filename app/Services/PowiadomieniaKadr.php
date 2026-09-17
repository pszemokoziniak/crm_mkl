<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Uprawnienie;
use App\Models\User;
use App\Models\ZgloszenieKierownika;
use App\Notifications\ZgloszenieKierownikaNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/** Dzwonek do tych, którzy obsługują ekran Kadry — po uprawnieniu, nie po roli. */
class PowiadomieniaKadr
{
    public function oZgloszeniu(ZgloszenieKierownika $zgloszenie, ?int $pomin = null): void
    {
        try {
            $odbiorcy = User::where('active', true)
                ->whereNull('deleted_at')
                ->when($pomin, fn ($q) => $q->where('id', '!=', $pomin))
                ->get()
                ->filter(fn (User $u) => $u->moze(Uprawnienie::ZMIANY_KADROWE));

            if ($odbiorcy->isNotEmpty()) {
                Notification::send($odbiorcy, new ZgloszenieKierownikaNotification($zgloszenie));
            }
        } catch (\Throwable $e) {
            Log::warning('Nie udało się powiadomić kadr o zgłoszeniu: '.$e->getMessage(), ['zgloszenie_id' => $zgloszenie->id]);
        }
    }
}
