<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
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
            'user_id' => $this->user_id,
            'date' => $this->work_date,
            'clock_in' => $this->clock_in,
            'clock_out' => $this->clock_out,
            'comment' => $this->note,

            'user' => UserResource::make($this->whenLoaded('user')),

            'breaks' => AttendanceBreakResource::collection(
                $this->whenLoaded('breaks')
            ),

            'applications' => ApplicationResource::collection(
                $this->whenLoaded('applications')
            ),

            'total_time' => $this->total_time,
            'total_break_time' => $this->total_break_time,
        ];
    }
}
