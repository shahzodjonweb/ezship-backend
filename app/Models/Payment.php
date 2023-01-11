<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $fillable = [
        'invoice_id',
        'invoice_link',
        'is_paid'
    ];

    public function payment_load()
    {
        return $this->belongsTo(Load::class);    
    }
}
