<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'title' => $this->title,
            'type' => $this->type,
            'category' => $this->category,
            'description' => $this->description,
            'date_time' => $this->date_time,
            'location' => $this->location,
            'image_url' => $this->image_url ? asset('storage/' . $this->image_url) : null,
            'contact_info' => $this->contact_info,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
