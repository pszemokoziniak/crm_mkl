<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToolFile extends Model
{
    protected $fillable = [
        'type',
        'filename',
        'nazwa',
        'glowne',
        'tool_id',
    ];

    protected $casts = [
        'glowne' => 'boolean',
    ];

    /**
     * Podpis widoczny na karcie sprzętu. Dopóki nikt go nie zmienił, pokazuje
     * nazwę pliku — inaczej wiersz byłby pusty.
     */
    public function etykieta(): string
    {
        return trim((string) $this->nazwa) !== '' ? $this->nazwa : $this->filename;
    }
}
