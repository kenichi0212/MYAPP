<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'company_id',
        'internal_product_code',
        'jan_code',
        'product_name',
        'maker_name',
        'name_source',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function storeAssignments(): HasMany
    {
        return $this->hasMany(ProductStoreAssignment::class);
    }
}
