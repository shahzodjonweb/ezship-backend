<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class Company extends Model
{
    use HasFactory, UUID;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
