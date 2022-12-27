<?php

namespace App\Http\Resources;

use App\Http\Resources\Location as LocationResource;

use Illuminate\Http\Resources\Json\JsonResource;

class Stop extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $stop_location = new  LocationResource($this->location);
        return [
            'id' => $this->id,
            'address' => $stop_location->address,
            'city' => $stop_location->city,
            'state' => $stop_location->state,
            'zip' => $stop_location->zip,
            'date' => $this->date,
            'lat' => $stop_location->lat,
            'lon' => $stop_location->lon,
        ];
    }
}
