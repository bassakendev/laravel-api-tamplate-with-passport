<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Loan;
use App\Models\User;
use App\Models\SavingGoal;
use App\Consts\ErrorMessages;
use App\Enums\GoalStatusEnum;
use App\Utils\TransactionUtils;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SavingGoalTest extends TestCase
{
    use RefreshDatabase;

    private static mixed $saving;
    private static mixed $request;
    private static mixed $wallet;

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
        $user->loanWallet->add(1000);

        $response = $this->postJson('api/auth/login', [
            'email' => 'johndoe@example.com',
            'remember_me' => 'present',
            'password' => 'password',
        ]);

        $logRes = $response->json();

        $request = $this->withHeaders([
            'Authorization' => 'Bearer ' . $logRes['access_token'],
        ]);

        $createdSaving = $request->postJson('api/savings/add', [
            'description' => 'My new car',
            'deadline' => now()->addDays(4)->format('Y-m-d'),
            'target_amount' => '13000.0',
            'current_amount' => '5600.0',
            'penalty_fees_per' => '0.5',
        ])->json();

        Self::$saving = $createdSaving;
        Self::$request = $request;
        Self::$wallet = $user->wallet;

    }

    /**
     * Get saving goals list.
     *
     * @return void
     */
    public function testUserCanFetchList()
    {
        $response = Self::$request->getJson('api/savings/list');

        $response->assertStatus(200);
    }


    /**
     * Test the creation of saving goal (In this step, the balance is sufficient)
     *
     * @return void
     */
    public function testUserCanCreateSavingGoalStep1()
    {
        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        $response = Self::$request->postJson('api/savings/add', [
            'description' => 'My new car',
            'deadline' => now()->addDays(4)->format('Y-m-d'),
            'target_amount' => '13000.0',
            'current_amount' => '5600.0',
            'penalty_fees_per' => '0.5',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGoal::class,
            'amount' => 5600.00,
            'fees' => 5600.00 * $internalFeesPer,
        ]);
    }


    /**
     * Test the creation of saving goal (In this step, the balance isn't sufficient)
     *
     * @return void
     */
    public function testUserCanCreateSavingGoalStep2()
    {
        $response = Self::$request->postJson('api/savings/add', [
            'description' => 'My new car',
            'deadline' => now()->addDays(4)->format('Y-m-d'),
            'target_amount' => '130000.0',
            'current_amount' => '56000.0',
            'penalty_fees_per' => '0.5',
        ]);

        $response->assertStatus(402);

        $this->assertTrue($response['message'] == ErrorMessages::$BALANCE_ERROR);

    }


    /**
     * Test the creation of saving goal (In this step, the request current_amount input is more than the request target_amount input)
     *
     * @return void
     */
    public function testUserCanCreateSavingGoalStep3()
    {
        $response = Self::$request->postJson('api/savings/add', [
            'description' => 'My new car',
            'deadline' => now()->addDays(4)->format('Y-m-d'),
            'target_amount' => '13000.0',
            'current_amount' => '45600.0',
            'penalty_fees_per' => '0.5',
        ]);

        $response->assertStatus(400);

        $this->assertTrue($response['message'] == ErrorMessages::$LOAN_INPUT_REQUEST_ERROR);
    }


    /**
     * Test  a saving goal request for detail.
     *
     * @return void
     */
    public function testSavingGoalDetails()
    {
        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        $savingDetailsRes = Self::$request->get('/api/savings/' . Self::$saving['id'] . '/details');

        $saving = $savingDetailsRes['data'];

        $savingDetailsRes->assertStatus(200);

        $this->assertTrue($saving['reason'] == 'My new car');
        $this->assertTrue($saving['transactions'][0]['fees'] == 5600.00 * $internalFeesPer);
    }


    /**
     * Test the progress of a saving goal (In this case,
     *  the user enters a reasonable amount. That is to say,
     *  which, added with the current sum, will not exceed the final amount.).
     *
     * @return void
     */
    public function testCanUpdateSavingGoalStep1()
    {
        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        $res = Self::$request->postJson('/api/savings/' . Self::$saving['id'] . '/update', [
            'amount' => '5000.0',
        ]);

        $res->assertStatus(201);
        $amount_without_fees = 5000.00;
        $fees = 5000.00 * $internalFeesPer;

        $this->assertTrue($res['amount'] == $amount_without_fees);
        $this->assertTrue($res['fees'] == $fees);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGoal::class,
            'amount' => $amount_without_fees,
            'fees' => $fees,
        ]);

        $this->assertDatabaseHas('saving_wallets', [
            'balance' => 10600.00,
        ]);
    }


    /**
     * Test the progress of a saving goal (In this case,
     * the user enters an unreasonable amount.
     * That is to say which, when added with the current sum,
     * will overflow the final amount.).
     *
     * @return void
     */
    public function testCanUpdateSavingGoalStep2()
    {
        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        $createdSaving = Self::$request->postJson('api/savings/add', [
            'description' => 'My new car',
            'deadline' => now()->addDays(4)->format('Y-m-d'),
            'target_amount' => '9000.0',
            'current_amount' => '7000.0',
            'penalty_fees_per' => '0.5',
        ])->json();

        $res = Self::$request->postJson('/api/savings/' . $createdSaving['id'] . '/update', [
            'amount' => '15000.0',
        ]);

        $res->assertStatus(201);

        $this->assertTrue($res['amount'] == 2000.00);
        $this->assertTrue($res['fees'] == 2000.00 * $internalFeesPer);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGoal::class,
            'amount' => 2000.00,
            'fees' => 2000.00 * $internalFeesPer,
        ]);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGoal::class,
            'amount' => 7000.00,
            'fees' => 0,
        ]);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGoal::class,
            'amount' => 9000.00,
            'fees' => 0,
        ]);

        $this->assertDatabaseHas('withdrawal_wallets', [
            'balance' => 9000.00,
        ]);

        $this->assertDatabaseHas('saving_goals', [
            'id' => $createdSaving['id'],
            'target_amount' => 9000.00,
            'current_amount' => 9000.00,
            'status' => GoalStatusEnum::REACHED->value,
        ]);

        $this->assertDatabaseHas('saving_wallets', [
            'balance' => 5600.00,
        ]);
    }


    /**
     * Test the progress of a saving goal (In this case,
     * the user enters an unreasonable amount.
     * That is to say which, added to the current sum,
     * will equal the final amount.).
     *
     * @return void
     */
    public function testCanUpdateSavingGoalStep3()
    {
        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        $createdSaving = Self::$request->postJson('api/savings/add', [
            'description' => 'My new car',
            'deadline' => now()->addDays(4)->format('Y-m-d'),
            'target_amount' => '10000.0',
            'current_amount' => '8000.0',
            'penalty_fees_per' => '0.5',
        ])->json();

        $res = Self::$request->postJson('/api/savings/' . $createdSaving['id'] . '/update', [
            'amount' => '2000.0',
        ]);

        $res->assertStatus(201);

        $this->assertTrue($res['amount'] == 2000.00);
        $this->assertTrue($res['fees'] == 2000.00 * $internalFeesPer);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGoal::class,
            'amount' => 2000.00,
            'fees' => 2000.00 * $internalFeesPer,
        ]);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGoal::class,
            'amount' => 8000.00,
            'fees' => 0,
        ]);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGoal::class,
            'amount' => 10000.00,
            'fees' => 0,
        ]);

        $this->assertDatabaseHas('withdrawal_wallets', [
            'balance' => 10000.00,
        ]);

        $this->assertDatabaseHas('saving_goals', [
            'id' => $createdSaving['id'],
            'target_amount' => 10000.00,
            'current_amount' => 10000.00,
            'status' => GoalStatusEnum::REACHED->value,
        ]);

        $this->assertDatabaseHas('saving_wallets', [
            'balance' => 5600.00,
        ]);
    }


    /**
     * Test the progress of a saving goal (In this case,
     * the user enters an unreasonable amount.
     * That is to say which, when added with the current sum,
     * will equal the final amount and this saving goal has loan.).
     *
     * @return void
     */
    public function testCanUpdateSavingGoalStep4()
    {
        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        $createdSaving = Self::$request->postJson('api/savings/add', [
            'description' => 'My new car',
            'deadline' => now()->addDays(4)->format('Y-m-d'),
            'target_amount' => '10000.0',
            'current_amount' => '8000.0',
            'penalty_fees_per' => '0.5',
        ])->json();

        $loan = Self::$request->postJson('api/loans/' . $createdSaving['id'] . '/ask', [
            'reason' => 'My new car',
            'amount' => '7500.0',
        ]);

        $res = Self::$request->postJson('/api/savings/' . $createdSaving['id'] . '/update', [
            'amount' => '2000.0',
        ]);

        $res->assertStatus(201);

        $this->assertTrue($res['amount'] == 2000.00);
        $this->assertTrue($res['fees'] == 2000.00 * $internalFeesPer);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGoal::class,
            'amount' => 2000.00,
            'fees' => 2000.00 * $internalFeesPer,
        ]);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGoal::class,
            'amount' => 8000.00,
            'fees' => 0,
        ]);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => Loan::class,
            'amount' => $loan['amount'],
            'reason' => 'Refund loan',
        ]);

        $this->assertDatabaseHas('withdrawal_wallets', [
            'balance' => 10000.00 - ($loan['amount'] + $loan['interest']),
        ]);

        $this->assertDatabaseHas('saving_goals', [
            'id' => $createdSaving['id'],
            'target_amount' => 10000.00,
            'current_amount' => 10000.00,
            'status' => GoalStatusEnum::REACHED->value,
        ]);

        $this->assertDatabaseHas('saving_wallets', [
            'balance' => 5600.00,
        ]);
    }


    /**
     * Test the cancellation of saving goal (In this case, the saving goal hasn't associeted loan).
     *
     * @return void
     */
    public function testCanCancelSavingGoalStep1()
    {
        $penaltyfeesPer = Self::$saving['penalty_fees_per'];

        $res = Self::$request->patch('/api/savings/' . Self::$saving['id'] . '/cancel');

        $res->assertStatus(201);

        $penalty = 5600.00 * $penaltyfeesPer;

        $this->assertTrue($res['amount'] == 5600.00);
        $this->assertTrue($res['fees'] == 0);

        $this->assertDatabaseHas('saving_goals', [
            'id' => Self::$saving['id'],
            'target_amount' => 13000.00,
            'current_amount' => 5600.00,
            'status' => GoalStatusEnum::CANCELLED->value,
        ]);

        $this->assertDatabaseHas('withdrawal_wallets', [
            'balance' => 5600.00 - $penalty,
        ]);

        $this->assertDatabaseHas('saving_wallets', [
            'balance' => 0,
        ]);
    }


    /**
     * Test the cancellation of saving goal. (In this case, the saving goal has refunded associeted loan.).
     *
     * @return void
     */
    public function testCanCancelSavingGoalStep2()
    {
        $penalty = Self::$saving['penalty_fees_per'];

        $loan = Self::$request->postJson('api/loans/' . Self::$saving['id'] . '/ask', [
            'reason' => 'My new car',
            'amount' => '5000.0',
        ]);

        Self::$request->postJson('/api/loans/' . $loan['id'] . '/refund', [
            'amount' => '6120.0',
        ]);

        $res = Self::$request->patch('/api/savings/' . Self::$saving['id'] . '/cancel');

        $res->assertStatus(201);

        $this->assertTrue($res['amount'] == 5600.00);
        $this->assertTrue($res['fees'] == 0);

        $this->assertDatabaseHas('saving_goals', [
            'id' => Self::$saving['id'],
            'target_amount' => 13000.00,
            'current_amount' => 5600.00,
            'status' => GoalStatusEnum::CANCELLED->value,
        ]);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGoal::class,
            'reason' => 'Cancellation with penalty of the savings goal.',
            'amount' => 5600.00,
        ]);

        $this->assertDatabaseHas('withdrawal_wallets', [
            'balance' => 5600.00 - 5600.00 * $penalty,
        ]);
    }

}
