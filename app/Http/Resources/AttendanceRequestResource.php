<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRequestResource extends JsonResource
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
            'attendance_id' => $this->attendance_id,
            'requested_clock_in' => $this->requested_clock_in,
            'requested_clock_out' => $this->requested_clock_out,
            'comment' => $this->note,
            'status' => $this->status,

            'break_requests' => BreakRequestResource::collection(
                $this->whenLoaded('breakRequests')
            ),
        ];
    }
}
