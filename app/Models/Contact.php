<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_AKTYWNY = 'Aktywny';
    public const STATUS_URLOP = 'Urlop';
    public const STATUS_ZWOLNIONY = 'Zwolniony';

    protected $fillable = [
        'first_name',
        'last_name',
        'birth_date',
        'pesel',
        'idCard_number',
        'idCard_date',
        'funkcja_id',
        'work_start',
        'work_end',
        'ekuz',
        'miejsce_urodzenia',
        'organization_id',
        'email',
        'phone',
        'address',
        'photo_path',
        'status_zatrudnienia',
    ];

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function funkcja()
    {
        return $this->belongsTo(Funkcja::class);
    }

    public function a1()
    {
        return $this->hasMany(A1::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organizationKierownikname() {
        return $this->hasOne(Organization::class);
    }

    /** Pobyty na budowach. */
    public function workDates(): HasMany
    {
        return $this->hasMany(ContactWorkDate::class);
    }

    /** Nieobecności — urlopy, zwolnienia itd. */
    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CtnDocument::class);
    }

    public function getNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function scopeOrderByName($query)
    {
        // Kolacja polska — inaczej ż/ś/ł mieszają się z z/s/l (domyślna
        // utf8mb4_unicode_ci traktuje je jako równe podstawowej literze).
        $query->orderByRaw('last_name COLLATE utf8mb4_polish_ci asc')
            ->orderByRaw('first_name COLLATE utf8mb4_polish_ci asc');
    }

    /**
     * Podział pracowników na dwie listy: kierownictwo (stanowiska oznaczone
     * w słowniku /funkcja) i pozostali. Kto nie ma stanowiska, trafia do
     * zwykłych pracowników — inaczej zniknąłby z obu list.
     */
    public function scopeKierownictwo($query, bool $tylkoKierownictwo)
    {
        $stanowiska = Funkcja::kierownictwoIds();

        if ($tylkoKierownictwo) {
            return $query->whereIn('funkcja_id', $stanowiska);
        }

        return $query->where(function ($q) use ($stanowiska) {
            $q->whereNotIn('funkcja_id', $stanowiska)
                ->orWhereNull('funkcja_id');
        });
    }

    /**
     * Słowa oddzielone spacją; "-słowo" wyklucza, "+słowo" to to samo, co
     * samo słowo (ludzie tak piszą, więc nie ma co ich karać). Sam "-" albo
     * "+" bez słowa nie znaczy nic.
     *
     * @return array{0: string[], 1: string[]} [muszą pasować, nie mogą pasować]
     */
    public static function rozbijWyszukiwanie(string $szukane): array
    {
        $musza = [];
        $nieMoga = [];

        foreach (preg_split('/\s+/u', trim($szukane)) ?: [] as $slowo) {
            $znak = mb_substr($slowo, 0, 1);
            $tresc = in_array($znak, ['-', '+'], true) ? mb_substr($slowo, 1) : $slowo;
            if ($tresc === '') {
                continue;
            }
            if ($znak === '-') {
                $nieMoga[] = $tresc;
            } else {
                $musza[] = $tresc;
            }
        }

        return [$musza, $nieMoga];
    }

    private static function pasujeDo($query, string $slowo): void
    {
        $query->where('first_name', 'like', '%'.$slowo.'%')
            ->orWhere('last_name', 'like', '%'.$slowo.'%')
            ->orWhereHas('funkcja', function ($query) use ($slowo) {
                $query->where('name', 'like', '%'.$slowo.'%');
            })
            // Szukanie po budowie — po nazwie albo po numerze. Bierzemy
            // wszystkie pobyty, także zakończone: kolumna "Koniec pobytu"
            // i tak pokazuje byłych, a zawężenie do obecnych daje filtr
            // Status → "Na budowie".
            ->orWhereHas('workDates.organization', function ($query) use ($slowo) {
                $query->where('nazwaBud', 'like', '%'.$slowo.'%')
                    ->orWhere('numerBud', 'like', '%'.$slowo.'%');
            });
    }

    private static function niePasujeDo($query, string $slowo): void
    {
        $dzis = now()->toDateString();

        $query->where('first_name', 'not like', '%'.$slowo.'%')
            ->where('last_name', 'not like', '%'.$slowo.'%')
            ->whereDoesntHave('funkcja', function ($query) use ($slowo) {
                $query->where('name', 'like', '%'.$slowo.'%');
            })
            ->whereDoesntHave('workDates', function ($query) use ($slowo, $dzis) {
                $query->activeOn($dzis)
                    ->whereHas('organization', function ($query) use ($slowo) {
                        $query->where('nazwaBud', 'like', '%'.$slowo.'%')
                            ->orWhere('numerBud', 'like', '%'.$slowo.'%');
                    });
            });
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            [$musza, $nieMoga] = self::rozbijWyszukiwanie($search);

            // Każde słowo musi pasować gdzieś (imię, nazwisko, stanowisko,
            // budowa), ale niekoniecznie w tym samym polu — "Jan Kowalski"
            // znajduje Jana Kowalskiego, choć imię i nazwisko to osobne kolumny.
            foreach ($musza as $slowo) {
                $query->where(fn ($q) => self::pasujeDo($q, $slowo));
            }

            // "-GW" odrzuca każdego, do kogo słowo pasuje. Przy budowie liczy
            // się tylko dzisiejszy pobyt: "bez tych, którzy pracują na GW"
            // ma nie wyrzucać kogoś, kto był tam dwa lata temu.
            foreach ($nieMoga as $slowo) {
                $query->where(fn ($q) => self::niePasujeDo($q, $slowo));
            }
        })->when($filters['trashed'] ?? null, function ($query, $trashed) {
            if ($trashed === 'with') {
                $query->withTrashed();
            } elseif ($trashed === 'only') {
                $query->onlyTrashed();
            }
        })->when($filters['status'] ?? null, function ($query, $status) {
            // "Na budowie" = pobyt aktywny dziś (start <= dziś <= end), jak w
            // kolumnie "Pracuje na budowie". "Dostępni" = odwrotność.
            $today = now()->toDateString();
            $activeSub = function ($q) use ($today) {
                $q->select('contact_id')->from('contact_work_dates')
                    ->whereNull('deleted_at')
                    ->where('start', '<=', $today)
                    ->where('end', '>=', $today);
            };
            if ($status === 'na_budowie') {
                $query->whereIn('id', $activeSub);
            } elseif ($status === 'dostepni') {
                // "Dostępny" ma znaczyć "można go wysłać na budowę", więc poza
                // wolnym terminem liczy się też to, że nadal pracuje w firmie.
                // Inaczej zwolniony figurował tu jako dostępny, a przy
                // przypisywaniu do budowy nie było go na liście.
                $query->whereNotIn('id', $activeSub)
                    ->where(function ($q) {
                        $q->whereNull('status_zatrudnienia')
                            ->orWhere('status_zatrudnienia', '!=', self::STATUS_ZWOLNIONY);
                    });
            }
        });
    }
}
