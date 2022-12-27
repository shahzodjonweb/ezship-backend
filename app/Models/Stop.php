<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class Stop extends Model
{
    use HasFactory , UUID;
    protected $fillable = [
       'id','date'
    ];
     public function location()
    {
        return $this->belongsTo(Location::class);
    }
      public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function stop_load()
    {
        return $this->belongsTo(Load::class);
    }
}
