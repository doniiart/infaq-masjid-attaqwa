<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConstructionDocument extends Model
{
    protected $fillable = [
        'construction_stage_id',
        'title',
        'description',
        'documented_at',
        'is_published',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'documented_at' => 'date',
            'is_published' => 'boolean',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ConstructionStage::class, 'construction_stage_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ConstructionDocumentPhoto::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}