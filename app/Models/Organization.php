<?php

namespace App\Models;

use App\Enums\Role;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Organization extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function contactWorkDates()
    {
        return $this->hasMany(ContactWorkDate::class, 'organization_id', 'id');
    }

    public function inzynier()
    {
        return $this->belongsTo(Contact::class, 'inzynier_id','id');
    }

    public function krajTyp()
    {
        return $this->belongsTo(KrajTyp::class, 'country_id','id');
    }
    public function kierownik()
    {
        return $this->belongsTo(Contact::class, 'kierownikBud_id','id');
    }

    /**
     * Budowy, na których dany kontakt jest lub BYŁ w kierownictwie:
     * ma wpis w contact_work_dates na tej budowie, a jego funkcja to
     * Kierownik lub Inżynier. Bez ograniczenia datą (aktywny lub były).
     * To definicja "budowy kierownika". Stare kolumny kierownikBud_id/
     * inzynier_id są ignorowane (relikt wczesnej wersji).
     */
    public function scopeManagedBy($query, ?int $contactId)
    {
        if (!$contactId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('contactWorkDates', function ($q) use ($contactId) {
            $q->where('contact_id', $contactId)
                ->whereHas('contact', function ($c) {
                    $c->whereIn('funkcja_id', [Funkcja::KIEROWNIK, Funkcja::INZYNIER]);
                });
        });
    }

    /**
     * Budowy, na których dany kontakt jest AKTYWNIE w kierownictwie DZIŚ
     * (wpis w contact_work_dates z activeOn: start <= dziś i (end NULL lub >= dziś),
     * funkcja Kierownik/Inżynier). Węższe niż managedBy — używane do decyzji
     * o DOSTĘPIE do budowy (kierownik wchodzi tylko na swoje aktywne budowy).
     */
    public function scopeActivelyManagedBy($query, ?int $contactId)
    {
        if (!$contactId) {
            return $query->whereRaw('1 = 0');
        }

        $today = now()->toDateString();

        return $query->whereHas('contactWorkDates', function ($q) use ($contactId, $today) {
            $q->where('contact_id', $contactId)
                ->activeOn($today)
                ->whereHas('contact', function ($c) {
                    $c->whereIn('funkcja_id', [Funkcja::KIEROWNIK, Funkcja::INZYNIER]);
                });
        });
    }

    /**
     * Pobyty, które jeszcze się nie skończyły — trwające i zaplanowane na przyszłość.
     * To one blokują archiwizację budowy; zakończone są już tylko historią.
     */
    public function unfinishedWorkDates()
    {
        return $this->contactWorkDates()->notFinished(Carbon::today()->toDateString());
    }

    /**
     * Ogranicza listę budów do widocznych dla użytkownika:
     * admin/biuro widzą wszystko, kierownik tylko swoje (kierownictwo — obecne lub byłe).
     */
    public function scopeVisibleTo($query, ?User $user)
    {
        if ($user && $user->isOffice()) {
            return $query;
        }

        if ($user && $user->isKierownik()) {
            return $query->managedBy($user->contactId());
        }

        return $query->whereRaw('1 = 0');
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('organizations.name', 'like', '%'.$search.'%')
                    ->orWhere('organizations.nazwaBud', 'like', '%'.$search.'%')
                    ->orWhere('organizations.numerBud', 'like', '%'.$search.'%')
                    ->orWhereHas('krajTyp', function ($query) use ($search) {
                        $query->where('name', 'like', '%'.$search.'%');
                    });
            });
        })->when($filters['trashed'] ?? null, function ($query, $trashed) {
            if ($trashed === 'with') {
                $query->withTrashed();
            } elseif ($trashed === 'only') {
                $query->onlyTrashed();
            } elseif ($trashed === 'my') {
                $contact = Contact::where('user_id', Auth::id())->first();
                $contact_id = $contact ? $contact->id : null;
                $query->where(function($q) use ($contact_id) {
                    $q->where('kierownikBud_id', $contact_id)
                      ->orWhere('inzynier_id', $contact_id)
                      ->orWhereHas('contactWorkDates', function ($q2) use ($contact_id) {
                          $q2->where('contact_id', $contact_id);
                      });
                })->withTrashed();
            }
        }, function ($query) {
            $query->whereNull('organizations.deleted_at');
        });
    }
}
