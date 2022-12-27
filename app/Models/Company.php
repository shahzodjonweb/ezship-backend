<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class Company extends Model
{
    use HasFactory, UUID;

    protected $fillable = [
        'id',
        'tax_id',
        'name',
        'address',
        'business_phone',
        'business_email',
        'representative_name',
        'representative_position',
        'sales_person_name',
        'sales_phone',
        'sales_email',
        'billing_address',
        'payment_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);    
    }
}
