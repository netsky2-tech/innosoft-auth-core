<?php

namespace InnoSoft\AuthCore\UI\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email_address' => $this->email,
            'is_2fa_enabled' => (bool) $this->two_factor_confirmed,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}