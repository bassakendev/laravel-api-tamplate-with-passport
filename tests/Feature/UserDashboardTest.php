<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class UserDashboardTest extends TestCase
{
    use RefreshDatabase;

    private static mixed $request;

    public function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '66666666',
            'email' => 'johndoe@example.com',
            'password' => bcrypt('password'),
            'activation_token' => 'john',
        ]);

        $user->wallet->add(50000);

        $response = $this->postJson('api/auth/login', [
            'email' => 'johndoe@example.com',
            'remember_me' => 'present',
            'password' => 'password',
        ]);

        $logRes = $response->json();

        $request = $this->withHeaders([
            'Authorization' => 'Bearer ' . $logRes['access_token'],
        ]);

        Self::$request = $request;
    }

    /**
     * Get Dashboar info
     *
     * @return void
     */
    public function testUserCanGetDashboardInfo()
    {
        $response = Self::$request->get('api/dashboard/common');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'total_loan_value',
            'total_saving_value',
            'pubs',
            'last_10_transactions'
        ]);
    }
}
