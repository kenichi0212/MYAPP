<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpiryCheckLog extends Model
{
    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'product_id',
        'store_id',
        'expiry_date',
        'quantity',
        'is_zero_report',
        'data_source',
        'checked_by',
        'checked_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'is_zero_report' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
