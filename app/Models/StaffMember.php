<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffMember extends Model
{
    use HasFactory;

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

    public function productAssignments(): HasMany
    {
        return $this->hasMany(ProductStoreAssignment::class, 'staff_master_id');
    }
}
