<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CtnDocument extends Model
{
    use HasFactory;
    use SoftDeletes;

    public static function create($name, $typ, $path, $contactId, $filename): self
    {
        $self = new self();
        $self->name = $name;
        $self->dokumentytyp_id = $typ;
        $self->path = $path;
        $self->contact_id = $contactId;
        $self->filename = $filename;

        return $self;
    }

    /** Przywracanie musi widzieć rekord z kosza — inaczej trasa daje 404. */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }

    public function dokumentytyp(): BelongsTo
    {
        return $this->belongsTo(DokumentyTyp::class);
    }
}
