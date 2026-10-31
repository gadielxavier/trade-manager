<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticker extends Model
{
    protected $fillable = [
        'code',
        'name',
        'long_name',
        'asset_type',
        'sub_type',
        'exchange',
        'currency',
        'sector',
        'subsector',
        'is_active',
        'logo_url',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    public function quotes(): HasOne
    {
        return $this->hasOne(Quote::class);
    }

}
