<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffMember extends Model
{
    protected $table = 'staff_master';

    const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'staff_name',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
