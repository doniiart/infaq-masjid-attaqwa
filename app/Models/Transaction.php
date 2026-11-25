<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $fillable = [
        'transaction_code',
        'type',
        'transaction_date',
        'amount',
        'source_or_purpose',
        'donor_name',
        'is_anonymous',
        'description',
        'status',
        'created_by',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'previous_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
            'is_anonymous' => 'boolean',
            'cancelled_at' => 'datetime',
            'previous_snapshot' => 'array',
        ];
    }

    public function getPublicDonorNameAttribute(): string
    {
        if ($this->type !== 'income') {
            return '-';
        }

        if ($this->is_anonymous || blank($this->donor_name)) {
            return 'Hamba Allah';
        }

        return $this->donor_name;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TransactionAttachment::class);
    }
}