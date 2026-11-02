<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class AttendanceTimeTest extends TestCase
{
    /**
     * ログイン済みユーザーが勤怠画面を表示できる
     */
    public function test_attendance_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertStatus(200);
    }
}
