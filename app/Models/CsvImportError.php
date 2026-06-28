<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsvImportError extends Model
{
    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'import_batch_id',
        'row_number',
        'error_reason',
        'raw_line',
    ];

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(CsvImportBatch::class, 'import_batch_id');
    }
}
