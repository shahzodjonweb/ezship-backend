<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Company as CompanyResource;
use Illuminate\Support\Facades\Auth;

class Customer extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $user = Auth::user();
        $loads_count  = $user->loads->where('status', '!=', 'initial')->count();
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'loads_count' => $loads_count,
            'loads' => $this->loads,
            'email_verified_at' => $this->email_verified_at,
            'has_company' => $this->has_company,
            'company' => new CompanyResource($this->company),
        ];
    }
}
