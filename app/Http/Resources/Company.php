<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Company extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'tax_id' => $this->tax_id,
            'name' => $this->name,
            'address' => $this->address,
            'business_phone' => $this->business_phone,
            'business_email' => $this->business_email,
            'representative_name' => $this->representative_name,
            'representative_position' => $this->representative_position,
            'sales_person_name' => $this->sales_person_name,
            'sales_phone' => $this->sales_phone,
            'sales_email' => $this->sales_email,
            'billing_address' => $this->billing_address,
            'payment_type' => $this->payment_type,
        ];
    }
}
