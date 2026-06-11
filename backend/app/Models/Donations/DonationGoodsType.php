<?php

namespace App\Models\Donations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DonationGoodsType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'is_type_countable',
        'unit',
        'danger_level',
    ];

    protected function casts(): array
    {
        return [
            'is_type_countable' => 'boolean',
        ];
    }
}
