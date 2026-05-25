<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $otherUser = $this->sender_id === auth()->id() ? $this->receiver : $this->sender;

        return [
            'id' => $this->id,
            'item' => [
                'id' => $this->item->id,
                'title' => $this->item->title,
                'image_url' => $this->item->image_url ? asset('storage/' . $this->item->image_url) : null,
            ],
            'other_user' => [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
            ],
            'last_message' => new MessageResource($this->messages()->latest()->first()),
            'updated_at' => $this->updated_at,
        ];
    }
}
