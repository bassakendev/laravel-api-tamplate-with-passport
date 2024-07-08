<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase;


    public function setUp(): void {
        parent::setUp();

        User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '66666666',
            'email' => 'johndoe@example.com',
            'password' => bcrypt('password'),
            'activation_token' => 'john',
        ]);
    }

    /**
     * Test user registration
     *
     * @return void
     */
    public function testUserCanRegister()
    {
        $response = $this->postJson('api/auth/register', [
            'last_name' => 'John',
            'first_name' => 'Doe',
            'phone' => '691439745',
            'email' => 'johndoe2@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'johndoe2@example.com',
        ]);
    }


    /**
     * Test user log in
     *
     * @return void
     */
    public function testUserCanLogin()
    {
        $response = $this->postJson('api/auth/login', [
            'email' => 'johndoe@example.com',
            'remember_me' => 'present',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_at'
        ]);
    }


    /**
     * Test user log out
     *
     * @return void
     */
   public function testAuthenticatedUserCanLogout()
    {

        $response = $this->postJson('api/auth/login', [
            'email' => 'johndoe@example.com',
            'remember_me' => 'present',
            'password' => 'password',
        ]);

        $token = $response->json();
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token['access_token'],
        ])->get('api/auth/logout');

        $response->assertStatus(204);
    }


    /**
     * Test user request for details
     *
     * @return void
     */
    public function testAuthenticatedUserCanGetUserDetails()
    {

        $response = $this->postJson('api/auth/login', [
            'email' => 'johndoe@example.com',
            'remember_me' => 'present',
            'password' => 'password',
        ]);

        $token = $response->json();
        $user = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token['access_token'],
        ])->get('/api/auth/user')->json()['data'];

        $response->assertStatus(200);

        $this->assertTrue($user['phone'] == '66666666');
        $this->assertTrue($user['email'] == 'johndoe@example.com');
    }
}
