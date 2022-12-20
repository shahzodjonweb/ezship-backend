<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'address', 'city', 'state', 'zip', 'date','lat','lon'
    ];
    public function post()
    {
        return $this->belongsTo(Load::class);
    }
}
