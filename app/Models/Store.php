<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'store_group_id',
        'store_code',
        'store_name',
        'office_name',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function storeGroup(): BelongsTo
    {
        return $this->belongsTo(StoreGroup::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function productAssignments(): HasMany
    {
        return $this->hasMany(ProductStoreAssignment::class);
    }

    public function checkLogs(): HasMany
    {
        return $this->hasMany(ExpiryCheckLog::class);
    }
}
