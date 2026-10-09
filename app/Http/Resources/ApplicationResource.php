<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
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
            'status' => $this->approval_status,
            'new_clock_in' => hisToHi($this->new_clock_in),
            'new_clock_out' => hisToHi($this->new_clock_out),
            'new_breaks' => BreakCorrectRequestResource::collection($this->breakCorrectRequests),
            'comment' => $this->comment,
        ];
    }
}
