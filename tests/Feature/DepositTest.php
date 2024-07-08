<?php

namespace Tests\Feature;

use App\Consts\ErrorMessages;
use Tests\TestCase;
use App\Models\User;
use App\Utils\TransactionUtils;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Enums\ActingWalletTypeEnum;

class DepositTest extends TestCase
{
    use RefreshDatabase;

    private static mixed $user1;
    private static mixed $user2;
    private static mixed $request;

    public function setUp(): void
    {
        parent::setUp();

        User::factory()->create([
            'first_name' => 'User1',
            'last_name' => 'Doe',
            'phone' => '66666666',
            'email' => 'user1@example.com',
            'password' => bcrypt('password'),
            'activation_token' => 'doe',
        ]);

        $response1 = $this->postJson('api/auth/login', [
            'email' => 'user1@example.com',
            'remember_me' => 'present',
            'password' => 'password',
        ]);

        $logRes1 = $response1->json();

        $request1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $logRes1['access_token'],
        ]);

        $user1Data = $request1->get('/api/auth/user')->json()['data'];

        Self::$user1 = $user1Data;
        Self::$request = $request1;
    }

    /**
     * Get deposit list.
     *
     * @return void
     */
    public function testUserCanFetchList()
    {
        $response = Self::$request->getJson('api/deposit/list');

        $response->assertStatus(200);
    }
}
