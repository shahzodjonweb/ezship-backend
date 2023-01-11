<?php

namespace App\Http\Resources;

use App\Http\Resources\Category as CategoryResource;
use App\Http\Resources\Stop as StopResource;
use App\Http\Resources\Payment as PaymentResource;
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
            'description' => $this->description,
            'phone' => $this->phone,
            'initial_price' => $this->initial_price,
            'counter_price' => $this->counter_price,
            'categories' => CategoryResource::collection($this->categories),
            'locations' => StopResource::collection($this->stops),
            'payment' => new PaymentResource($this->payment),
            'created_at' => $this->created_at->format('d/m/Y'),
            'updated_at' => $this->updated_at->format('d/m/Y'),
        ];
    }
}
