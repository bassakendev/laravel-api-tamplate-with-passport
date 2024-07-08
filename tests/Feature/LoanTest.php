<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Loan;
use App\Models\User;
use App\Consts\ErrorMessages;
use App\Enums\LoanStatusEnum;
use App\Utils\TransactionUtils;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoanTest extends TestCase
{
    use RefreshDatabase;

    private static mixed $saving;
    private static mixed $request;
    private static mixed $wallet;
    private static mixed $loan;
    private static mixed $loan2;

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

        $createdSaving = $request->postJson('api/savings/add', [
            'description' => 'My new car',
            'deadline' => now()->addDays(4)->format('Y-m-d'),
            'target_amount' => '13000.0',
            'current_amount' => '9600.0',
            'penalty_fees_per' => '0.5',
        ])->json();

        $createdSaving2 = $request->postJson('api/savings/add', [
            'description' => 'My phone',
            'deadline' => now()->addDays(4)->format('Y-m-d'),
            'target_amount' => '13000.0',
            'current_amount' => '9600.0',
            'penalty_fees_per' => '0.5',
        ])->json();

        $createdLoan = $request->postJson('api/loans/' . $createdSaving2['id'] . '/ask', [
            'reason' => 'My phone',
            'amount' => '5100.0',
        ]);

        $createdSaving3 = $request->postJson('api/savings/add', [
            'description' => 'My phone',
            'deadline' => now()->addDays(4)->format('Y-m-d'),
            'target_amount' => '13000.0',
            'current_amount' => '9600.0',
            'penalty_fees_per' => '0.5',
        ])->json();

        $createdLoan2 = $request->postJson('api/loans/' . $createdSaving3['id'] . '/ask', [
            'reason' => 'My phone',
            'amount' => '6000.0',
        ]);

        Self::$saving = $createdSaving;
        Self::$loan = $createdLoan;
        Self::$loan2 = $createdLoan2;
        Self::$request = $request;
        Self::$wallet = $user->wallet;
    }


    /**
     * Get loans list.
     *
     * @return void
     */
    public function testUserCanFetchList()
    {
        $response = Self::$request->getJson('api/loans/list');

        $response->assertStatus(200);
    }


    /**
     * Test if a user can request a loan with a savings as collateral. (In this case the amount requested is correct and respects the constraints.).
     *
     * @return void
     */
    public function testUserCanAskLoanWithSavingGoalAsCollateralStep1()
    {
        $loanfeesPer = TransactionUtils::getLoanFeesPer();

        $response = Self::$request->postJson('api/loans/' . Self::$saving['id'] . '/ask', [
            'reason' => 'My new car',
            'amount' => '5100.0',
        ]);

        $response->assertStatus(201);

        $this->assertTrue($response['amount'] == 5100.00);
        $this->assertTrue($response['reason'] == 'My new car');
        $this->assertTrue($response['interest'] == 5100.00 * $loanfeesPer);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => Loan::class,
            'amount' => 5100.0,
            'reason' => 'My new car',
            'fees' => 0,
        ]);

        $this->assertDatabaseHas('admin_loan_wallets', [
            'balance' => 16200.0, //We created two loans totaling 5100 and another one of 6000 each.
        ]);

        $this->assertDatabaseHas('withdrawal_wallets', [
            'balance' => 0.0,
        ]);

        $this->assertDatabaseHas('loan_wallets', [
            'balance' => 16200.0, //We created two loans totaling 5100 and another one of 6000 each.
        ]);

        $this->assertDatabaseHas('loans', [
            'amount' => 5100.0,
            'interest' => 5100.0 * $loanfeesPer,
        ]);
    }


    /**
     * Test if a user can request a loan with a savings as collateral. (In this case the amount requested is correct but the savings goal pledged will not be able to repay the loan.).
     *
     * @return void
     */
    public function testUserCanAskLoanWithSavingGoalAsCollateralStep2()
    {
        $createdSaving = Self::$request->postJson('api/savings/add', [
            'description' => 'My new car',
            'deadline' => now()->addDays(4)->format('Y-m-d'),
            'target_amount' => '13000.0',
            'current_amount' => '6000.0',
            'penalty_fees_per' => '0.5',
        ]);

        $response = Self::$request->postJson('api/loans/' . $createdSaving['id'] . '/ask', [
            'reason' => 'My new car',
            'amount' => '7000.0',
        ]);

        $response->assertStatus(402);

        $this->assertTrue($response['message'] == ErrorMessages::$INSUFFICIENT_PLEDGE);
    }


    /**
     * Test if a user can request a loan with a savings as collateral. (In this case the saving goal is already take as pledge.).
     *
     * @return void
     */
    public function testUserCanAskLoanWithSavingGoalAsCollateralStep3()
    {
        $createdSaving = Self::$request->postJson('api/savings/add', [
            'description' => 'My new car',
            'deadline' => now()->addDays(4)->format('Y-m-d'),
            'target_amount' => '13000.0',
            'current_amount' => '6000.0',
            'penalty_fees_per' => '0.5',
        ]);

        $response = Self::$request->postJson('api/loans/' . $createdSaving['id'] . '/ask', [
            'reason' => 'My new car',
            'amount' => '5500.0',
        ]);

        $response = Self::$request->postJson('api/loans/' . $createdSaving['id'] . '/ask', [
            'reason' => 'My new car',
            'amount' => '5200.0',
        ]);

        $response->assertStatus(402);

        $this->assertTrue($response['message'] == ErrorMessages::$PLEDGE_ALREADY_USED);
    }

    /**
     * Test a loan request for detail.
     *
     * @return void
     */
    public function testLoanDetails()
    {
        $loanDetailsRes = Self::$request->get('/api/loans/' . Self::$loan['id'] . '/details');

        $loan = $loanDetailsRes['data'];

        $loanDetailsRes->assertStatus(200);

        $this->assertTrue($loan['reason'] == 'My phone');
    }


    /**
     * Test the refund of loan (In this case,
     *  the user enters a reasonable amount. That is to say,
     *  which, added with the current sum, will not exceed the final amount.).
     *
     * @return void
     */
    public function testCanRefundedLoanStep1()
    {
        $loanFeesPer = TransactionUtils::getLoanFeesPer();

        $res = Self::$request->postJson('api/loans/' . Self::$loan['id'] . '/refund', [
            'amount' => '3000.0',
        ]);

        $res->assertStatus(201);

        $this->assertTrue($res['amount'] == 3000.00);
        $this->assertTrue($res['fees'] == 0);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => Loan::class,
            'amount' => 3000.00,
            'fees' => 0,
        ]);

        $this->assertDatabaseHas('loans', [
            'amount' => 5100.00,
            'interest' => 5100.00 * $loanFeesPer,
            'amount_refunded' => 3000.00,
            'status' => LoanStatusEnum::NOT_REFUNDED->value,
        ]);
    }


    /**
     * Test the refund of loan (In this case,
     * the user enters an unreasonable amount.
     * That is to say which, when added with the current sum,
     * will overflow the final amount.).
     *
     * @return void
     */
    public function testCanRefundedLoanStep2()
    {
        $loanFeesPer = TransactionUtils::getLoanFeesPer();

        $res = Self::$request->postJson('/api/loans/' . Self::$loan['id'] . '/refund', [
            'amount' => '15000.0',
        ]);

        $res->assertStatus(201);

        $amountPaid = 5100.00 + 5100.00 * $loanFeesPer;

        $this->assertTrue($res['amount'] == 5100.00);
        $this->assertTrue($res['fees'] == 0);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => Loan::class,
            'amount' => $amountPaid,
            'fees' => 0,
        ]);

        $this->assertDatabaseHas('loan_wallets', [
            'balance' => 6000, //The last loan of 6000 of thes last test.
        ]);

        $this->assertDatabaseHas('loans', [
            'amount' => 5100.00,
            'interest' => 5100.00 * $loanFeesPer,
            'amount_refunded' => 5100.00 + 5100.00 * $loanFeesPer,
            'status' => LoanStatusEnum::REFUNDED->value,
        ]);
    }


    /**
     * Test loan repayment (In this case,
     * the user enters an unreasonable amount.
     * That is to say which, added to the current sum,
     * will equal the final amount.).
     *
     * @return void
     */
    public function testCanRefundedLoanStep3()
    {
        $loanFeesPer = TransactionUtils::getLoanFeesPer();

        $res = Self::$request->postJson('/api/loans/' . Self::$loan2['id'] . '/refund', [
            'amount' => '6120.0',
        ]);

        $res->assertStatus(201);

        $amountPaid = 6000.00 + 6000.00 * $loanFeesPer;

        $this->assertTrue($res['amount'] == 6000.00);
        $this->assertTrue($res['fees'] == 0);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => Loan::class,
            'amount' => $amountPaid,
            'fees' => 0,
        ]);

        $this->assertDatabaseHas('loan_wallets', [
            'balance' => 5100.00, //The last loan of 5100 of thes previous test.
        ]);

        $this->assertDatabaseHas('loans', [
            'amount' => 6000.00,
            'interest' => 6000.00 * $loanFeesPer,
            'amount_refunded' => 6000.00 + 6000.00 * $loanFeesPer,
            'status' => LoanStatusEnum::REFUNDED->value,
        ]);
    }


    /**
     * Test loan repayment (In this case, we are trying to repay a loan that has already been repaid.).
     *
     * @return void
     */
    public function testCanRefundedLoanStep4()
    {

        Self::$request->postJson('/api/loans/' . Self::$loan2['id'] . '/refund', [
            'amount' => '6120.0',
        ]);

        $res = Self::$request->postJson('/api/loans/' . Self::$loan2['id'] . '/refund', [
            'amount' => '6120.0',
        ]);

        $res->assertStatus(404);

        $this->assertTrue($res['message'] == ErrorMessages::$LOAN_MODEL_ERROR);
    }

}
