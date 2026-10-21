<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'note',
        'work_date',
        'clock_in',
        'clock_out',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function attendanceRequests()
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    public function getTotalBreakTimeAttribute(): string
    {
        $totalMinutes = 0;

        foreach ($this->breaks as $break) {
            if ($break->break_start && $break->break_end) {
                $start = Carbon::parse($break->break_start);
                $end = Carbon::parse($break->break_end);

                $totalMinutes += $start->diffInMinutes($end);
            }
        }

        return sprintf(
            '%02d:%02d',
            intdiv($totalMinutes, 60),
            $totalMinutes % 60
        );
    }

    public function getTotalTimeAttribute(): ?string
    {
        if (!$this->clock_in || !$this->clock_out) {
            return null;
        }

        $start = Carbon::parse($this->clock_in);
        $end = Carbon::parse($this->clock_out);

        $totalMinutes = $start->diffInMinutes($end)
            - $this->total_break_time_minutes;

        $totalMinutes = max(0, $totalMinutes);

        return sprintf(
            '%02d:%02d',
            intdiv($totalMinutes, 60),
            $totalMinutes % 60
        );
    }

    public function getTotalBreakTimeMinutesAttribute(): int
    {
        $totalMinutes = 0;

        foreach ($this->breaks as $break) {
            if ($break->break_start && $break->break_end) {
                $start = Carbon::parse($break->break_start);
                $end = Carbon::parse($break->break_end);

                $totalMinutes += $start->diffInMinutes($end);
            }
        }

        return $totalMinutes;
    }
}
