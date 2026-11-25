<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConstructionDocumentPhoto extends Model
{
    protected $fillable = [
        'construction_document_id',
        'file_path',
        'caption',
        'sort_order',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(ConstructionDocument::class, 'construction_document_id');
    }
}