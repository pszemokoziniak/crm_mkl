<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuildingTimeSheet extends Model
{
    use HasFactory;

    /**  Build  */
    public function build(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
