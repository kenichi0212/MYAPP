<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'name',
    ];

    public function storeGroups(): HasMany
    {
        return $this->hasMany(StoreGroup::class);
    }

    public function staffMembers(): HasMany
    {
        return $this->hasMany(StaffMember::class);
    }
}
