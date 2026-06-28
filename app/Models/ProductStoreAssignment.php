<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStoreAssignment extends Model
{
    protected $fillable = [
        'company_id',
        'product_id',
        'store_id',
        'staff_master_id',
        'import_batch_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'staff_master_id');
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(CsvImportBatch::class, 'import_batch_id');
    }
}
