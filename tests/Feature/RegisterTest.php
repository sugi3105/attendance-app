<?php

namespace Tests\Feature;

use Tests\TestCase;

class RegisterTest extends TestCase
{
    /**
     * 
     */
    public function test_name_is_required(): void
    {
        $response = $this->from('/register')
            ->post('/register', [
                'name' => '',
                'email' => 'test-register@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $this->assertSame(302, $response->status());
        $this->assertNotNull($response->headers->get('Location'));
    }
}
