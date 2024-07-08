<?php

namespace Tests\Feature;

use App\Consts\ErrorMessages;
use Tests\TestCase;
use App\Models\User;
use App\Utils\TransactionUtils;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Enums\ActingWalletTypeEnum;

class TransferTest extends TestCase
{
    use RefreshDatabase;

    private static mixed $user1;
    private static mixed $user2;
    private static mixed $request;

    public function setUp(): void
    {
        parent::setUp();

        $user1 = User::factory()->create([
            'first_name' => 'User1',
            'last_name' => 'Doe',
            'phone' => '66666666',
            'email' => 'user1@example.com',
            'password' => bcrypt('password'),
            'activation_token' => 'doe',
        ]);

        User::factory()->create([
            'first_name' => 'User2',
            'last_name' => 'Jhon',
            'phone' => '66666666',
            'email' => 'user2@example.com',
            'password' => bcrypt('password'),
            'activation_token' => 'john',
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

        $user2 = User::where('email', 'user2@example.com')->first();

        $user1->wallet->add(100000.00);

        Self::$user1 = $user1Data;
        Self::$user2 = $user2;
        Self::$request = $request1;
    }

    /**
     * Get transfer list.
     *
     * @return void
     */
    public function testUserCanFetchList()
    {
        $response = Self::$request->getJson('api/transfer/list');

        $response->assertStatus(200);
    }


    /**
     * Transfer money (In this case, the user send money to another user).
     *
     * @return void
     */
    public function testUserCanTransferMoneyStep1()
    {
        $internal_fees_per = TransactionUtils::getInternalFeesPer();

        $response = Self::$request->postJson('api/transfer/', [
            'amount' => '6000.0',
            'referral_code' => Self::$user2->referral->code,
            'wallet_type' => ActingWalletTypeEnum::MAIN->value,
        ]);

        $response->assertStatus(201);

        $this->assertTrue($response['sender']['id'] == Self::$user1['id']);
        $this->assertTrue($response['recipient']['id'] == Self::$user2->id);
        $this->assertTrue($response['amount'] == 6000.0);

        $fees = 6000.0 * $internal_fees_per;

        $this->assertDatabaseHas('admin_wallets', [
            'balance' => $fees,
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => Self::$user2->id,
            'balance' => 6000.00,
        ]);
    }

    /**
     * Transfer money (In this case, the user send money to himself).
     *
     * @return void
     */
    public function testUserCanTransferMoneyStep2()
    {
        $response = Self::$request->postJson('api/transfer/', [
            'amount' => '6000.0',
            'referral_code' => Self::$user1['referral_code'],
            'wallet_type' => ActingWalletTypeEnum::MAIN->value,
        ]);

        $response->assertStatus(410);

        $this->assertTrue($response['message'] == ErrorMessages::$TRANSFER_CONFLICT);
    }
}
