<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Grupa sprzętu — "Kontener", "Manitou", "Przyczepa". Zbiera modele,
 * a te dopiero sztuki. Grupa może być pusta: zakłada się ją zanim
 * pojawi się w niej pierwszy sprzęt.
 */
class GrupaSprzetu extends Model
{
    use HasFactory;

    protected $table = 'grupy_sprzetu';

    protected $fillable = ['nazwa'];

    /** @return HasMany<NarzedziaTyp> */
    public function typy(): HasMany
    {
        return $this->hasMany(NarzedziaTyp::class, 'grupa_id');
    }

    /**
     * Grupa o tej nazwie — zakładana, jeśli jeszcze jej nie ma. Formularze
     * sprzętu pozwalają wpisać nową grupę w locie i nie mają skąd znać id.
     */
    public static function zNazwy(?string $nazwa): ?self
    {
        $nazwa = trim((string) $nazwa);

        if ($nazwa === '') {
            return null;
        }

        return static::firstOrCreate(['nazwa' => $nazwa]);
    }

    /** @return array<int, string> */
    public static function nazwy(): array
    {
        return static::orderBy('nazwa')->pluck('nazwa')->all();
    }
}
