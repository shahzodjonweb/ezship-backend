<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Load extends JsonResource
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
            'type' => $this->type,
            'status' => $this->status,
            'description'=> $this->description,
            'phone'=> $this->phone,
            'initial_price'=> $this->initial_price,
            'pickup_address'=> $this->pickup_address,
            'pickup_date'=> $this->pickup_date,
            'delivery_address'=> $this->delivery_address,
            'delivery_date'=> $this->delivery_date,
            'created_at' => $this->created_at->format('d/m/Y'),
            'updated_at' => $this->updated_at->format('d/m/Y'),
        ];
    }
}
