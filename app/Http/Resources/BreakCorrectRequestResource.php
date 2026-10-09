<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BreakCorrectRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'new_break_in' => hisToHi($this->new_break_in),
            'new_break_out' => hisToHi($this->new_break_out),
        ];
    }
}
