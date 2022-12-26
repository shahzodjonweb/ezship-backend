<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Load extends Model
{
    use HasFactory, UUID;

    protected $fillable = [
        'type',
        'status',
        'description',
        'phone',
        'initial_price',
        'counter_price'
    ];

    public function categories()
    {
        return $this->hasMany(Category::class);
    }
    public function stops()
    {
        return $this->hasMany(Stop::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
