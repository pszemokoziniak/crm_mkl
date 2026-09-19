<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Osoba na koszcie: zakwaterowana w pokoju (od–do) albo wskazana do podziału. */
class KosztOsoba extends Model
{
    protected $table = 'koszty_osoby';

    protected $fillable = ['koszt_id', 'contact_id', 'od', 'do'];

    protected $casts = ['od' => 'date:Y-m-d', 'do' => 'date:Y-m-d'];

    public function koszt(): BelongsTo
    {
        return $this->belongsTo(Koszt::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }
}
