<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CsvImportBatch extends Model
{
    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'file_name',
        'scope',
        'store_group_id',
        'imported_by',
        'imported_at',
        'detected_encoding',
        'total_rows',
        'success_count',
        'error_count',
    ];

    protected function casts(): array
    {
        return [
            'imported_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function storeGroup(): BelongsTo
    {
        return $this->belongsTo(StoreGroup::class);
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function errors(): HasMany
    {
        return $this->hasMany(CsvImportError::class, 'import_batch_id');
    }
}
