<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreGroup extends Model
{
    const UPDATED_AT = null;
    const CREATED_AT = null;

    protected $fillable = [
        'company_id',
        'group_code',
        'group_name',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function csvImportBatches(): HasMany
    {
        return $this->hasMany(CsvImportBatch::class);
    }
}
