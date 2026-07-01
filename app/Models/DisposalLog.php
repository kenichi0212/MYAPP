<?php

namespace App\Models;

use App\Enums\ProcessType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisposalLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'product_id',
        'store_id',
        'expiry_date',
        'process_type',
        'quantity',
        'processed_by',
        'processed_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date'  => 'date',
            'process_type' => ProcessType::class,
            'processed_at' => 'datetime',
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

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
