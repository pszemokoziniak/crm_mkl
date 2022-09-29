<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CtnDocument extends Model
{
    use HasFactory;

    public static function create($name, $typ, $path, $contactId, $filename): self
    {
        $self =  new self();
        $self->name = $name;
        $self->dokumentytyp_id = $typ;
        $self->path = $path;
        $self->contact_id = $contactId;
        $self->filename = $filename;

        return $self;
    }

    public function dokumentytyp()
    {
        return $this->belongsTo(DokumentyTyp::class);
    }
}
