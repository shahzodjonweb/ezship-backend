<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class Load extends Model
{
    use HasFactory, UUID;

    protected $fillable = [
        'type', 
        'description',
        'phone',
        'initial_price',
        'pickup_address',
        'pickup_date',
        'delivery_address',
        'delivery_date',
    ];

    public function categories()
    {
        return $this->hasMany(Category::class);
    }
}
