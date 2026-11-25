<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConstructionStage extends Model
{
    protected $fillable = [
        'title',
        'description',
        'stage_order',
        'status',
        'progress_percentage',
        'start_date',
        'end_date',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ConstructionDocument::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}