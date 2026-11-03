<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['farm_id', 'name', 'tree_count', 'sort_order'])]
class FarmVariety extends Model
{
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }
}
