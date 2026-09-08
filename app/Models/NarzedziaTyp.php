<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NarzedziaTyp extends Model
{
    use HasFactory;

    /** Egzemplarze tego modelu — do liczników na ekranie grup. */
    public function narzedzias(): HasMany
    {
        return $this->hasMany(Narzedzia::class, 'narzedzia_typ_id');
    }

    /** @return BelongsTo<GrupaSprzetu, NarzedziaTyp> */
    public function grupa(): BelongsTo
    {
        return $this->belongsTo(GrupaSprzetu::class, 'grupa_id');
    }

    protected $fillable = [
        'name',
        'grupa_id',
    ];

    /** Nazwa grupy albo null — na ekranach mówimy nazwami, nie identyfikatorami. */
    public function nazwaGrupy(): ?string
    {
        return optional($this->grupa)->nazwa;
    }
}
