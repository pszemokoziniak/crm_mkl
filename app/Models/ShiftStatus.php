<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftStatus extends Model
{
    protected $table = 'shift_status';
    use HasFactory;
    use SoftDeletes;
    protected $dates = ['deleted_at'];

    /** Do czego zaliczamy godziny z tym statusem w statystykach budowy. */
    public const KAT_PRACA = 'praca';
    public const KAT_URLOP = 'urlop';
    public const KAT_ZWOLNIENIE = 'zwolnienie';
    public const KAT_NIEOBECNOSC = 'nieobecnosc';
    public const KAT_SWIETO = 'swieto';

    /** @var array<string, string> podpisy do słownika i statystyk */
    public const KATEGORIE = [
        self::KAT_PRACA => 'Praca',
        self::KAT_URLOP => 'Urlop',
        self::KAT_ZWOLNIENIE => 'Zwolnienie lekarskie',
        self::KAT_NIEOBECNOSC => 'Nieobecność nieusprawiedliwiona',
        self::KAT_SWIETO => 'Święto',
    ];

    protected $fillable = ['title', 'code', 'kategoria'];

    /**
     * Identyfikatory statusów danej kategorii.
     *
     * @return int[]
     */
    public static function idsKategorii(string $kategoria): array
    {
        return static::withTrashed()->where('kategoria', $kategoria)->pluck('id')->all();
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            });
        })->when($filters['role'] ?? null, function ($query, $role) {
            $query->whereRole($role);
        })->when($filters['trashed'] ?? null, function ($query, $trashed) {
            if ($trashed === 'with') {
                $query->withTrashed();
            } elseif ($trashed === 'only') {
                $query->onlyTrashed();
            }
        });
    }
}
