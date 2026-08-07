<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakRequest extends Model
{
    use HasFactory;

    public function attendanceRequest()
    {
        return $this->belongsTo(AttendanceRequest::class);
    }
}
