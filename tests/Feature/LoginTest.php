<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginTest extends TestCase
{
    /**
     * 
     */
    public function test_email_is_required(): void
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * 
     */
    public function test_password_is_required(): void
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * 
     */
    public function test_invalid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'not-registered@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
