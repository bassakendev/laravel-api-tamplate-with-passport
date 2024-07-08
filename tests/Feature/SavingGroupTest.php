<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Utils\DateUtils;
use App\Models\SavingGoal;
use App\Models\AdminWallet;
use App\Models\SavingGroup;
use App\Consts\ErrorMessages;
use App\Enums\GoalStatusEnum;
use App\Utils\TransactionUtils;
use App\Models\WithdrawalWallet;
use App\Models\SavingGroupMember;
use App\Enums\SavingGroupTypeEnum;
use App\Enums\SavingGroupStatusEnum;
use App\Enums\SavingGroupMemberSatusEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SavingGroupTest extends TestCase
{
    use RefreshDatabase;

    private static mixed $user1;
    private static mixed $user2;
    private static mixed $admin_wallet_balance;
    private static mixed $saving_wallet_balance;
    private static mixed $member2;
    private static mixed $request;
    private static mixed $normal_group;
    private static mixed $challenge_group;

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

        $normal_group = $request1->postJson('api/groups/create-normal', [
            'name' => 'Protection',
            'description' => 'Protection of city womans',
            'deadline' => now()->addDays(3)->format('Y-m-d'),
            'target_amount_per_member' => '15000.0',
            'penalty_fees_per' => '0.03',
        ])->json();

        $challenge_group = Self::$request->postJson('api/groups/create-challenge', [
            'name' => 'Secure',
            'description' => 'Security of city womans',
            'number_of_period' => 4,
            'contribution_frequency' => 'monthly',
            'admission_fees' => '3500.0',
            'target_amount_per_member' => '25000.0',
            'penalty_fees_per' => '0.03',
        ]);

        // dd($group);

        $member2 = SavingGroupMember::where('user_id', $user2->id)->where('saving_group_id', $normal_group['id'])->first();

        $min_amount = TransactionUtils::getMinAmount();
        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        Self::$normal_group = $normal_group;
        Self::$challenge_group = $challenge_group;
        Self::$saving_wallet_balance = 3500 + $min_amount;
        Self::$admin_wallet_balance = (3500 + $min_amount) * $internalFeesPer;
        Self::$member2 = $member2;
    }


    /**
     * Get saving group list.
     *
     * @return void
     */
    public function testUserCanFetchList()
    {
        $response = Self::$request->getJson('api/groups/list');

        $response->assertStatus(200);
    }

    /**
     * Get saving group details.
     *
     * @return void
     */
    public function testUserCanFetchSavingGroupDetails()
    {
        $groupDetailsRes = Self::$request->get('/api/groups/' . Self::$normal_group['id'] . '/details');

        $group = $groupDetailsRes['data'];

        $groupDetailsRes->assertStatus(200);

        $this->assertTrue($group['name'] == 'Protection');
    }


    /**
     * Test if a user can create normal saving group.(In this case the parameter deatline is incorrect).
     *
     * @return void
     */
    public function testCanCreateNormalSavingGroupstep1()
    {
        $internal_fees_per = TransactionUtils::getInternalFeesPer();
        $min_amount = TransactionUtils::getMinAmount();

        $response = Self::$request->postJson('api/groups/create-normal', [
            'name' => 'Secure',
            'description' => 'Security of city womans',
            'deadline' => now()->addDays(3)->format('Y-m-d'),
            'target_amount_per_member' => '25000.0',
            'penalty_fees_per' => '0.03',
        ]);

        $response->assertStatus(201);

        $this->assertTrue($response['admin_id'] == Self::$user1['id']);
        $this->assertTrue($response['name'] == 'Secure');
        $this->assertTrue($response['description'] == 'Security of city womans');
        $this->assertTrue($response['target_amount_per_member'] == 25000);
        $this->assertTrue($response['penalty_fees_per'] == 0.03);

        $this->assertDatabaseHas('saving_groups', [
            'name' => 'Secure',
            'description' => 'Security of city womans',
            'target_amount_per_member' => 25000,
            'penalty_fees_per' => '0.03',
            'total_members' => 1,
            'total_amount' => $min_amount,
            'status' => GoalStatusEnum::INPROGRESS->value,
            'type' => SavingGroupTypeEnum::NORMAL->value,
        ]);

        $this->assertDatabaseHas('saving_wallets', [
            'user_id' => Self::$user1['id'],
            'balance' => $min_amount + Self::$saving_wallet_balance, // Considering the groups created in the setUp function.
        ]);

        $fees = $min_amount * $internal_fees_per;

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGroup::class,
            'amount' => $min_amount,
            'fees' => $fees,
        ]);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGroup::class,
            'amount' => $min_amount,
            'fees' => 0,
        ]);

        $this->assertDatabaseHas('saving_group_members', [
            'user_id' => Self::$user1['id'],
            'is_admin' => true,
            'current_amount' => $min_amount,
            'status' => SavingGroupMemberSatusEnum::ACTIVE->value
        ]);

        $this->assertDatabaseHas('admin_wallets', [
            'balance' => $fees + Self::$admin_wallet_balance, // Considering the groups created in the setUp function.
        ]);
    }


    /**
     * Test if a user can create normal saving group.(In this case the parameter deatline is correct).
     *
     * @return void
     */
    public function testCanCreateNormalSavingGroupstep2()
    {
        $response = Self::$request->postJson('api/groups/create-normal', [
            'name' => 'Secure',
            'description' => 'Security of city womans',
            'deadline' => now()->format('Y-m-d'),
            'target_amount_per_member' => '25000.0',
            'penalty_fees_per' => '0.03',
        ]);

        $response->assertStatus(422);

        $this->assertTrue($response['message'] == ErrorMessages::$BAD_DATE);
    }


    /**
     * Test if a user can create challenge saving group.
     *
     * @return void
     */
    public function testCanCreateChallengeSavingGroups()
    {
        $internal_fees_per = TransactionUtils::getInternalFeesPer();
        $min_amount = 3500.0;

        $response = Self::$request->postJson('api/groups/create-challenge', [
            'name' => 'Secure',
            'description' => 'Security of city womans',
            'number_of_period' => 4,
            'contribution_frequency' => 'monthly',
            'admission_fees' => '3500.0',
            'target_amount_per_member' => '25000.0',
            'penalty_fees_per' => '0.03',
        ]);

        $response->assertStatus(201);

        $this->assertTrue($response['admin_id'] == Self::$user1['id']);
        $this->assertTrue($response['name'] == 'Secure');
        $this->assertTrue($response['description'] == 'Security of city womans');
        $this->assertTrue($response['target_amount_per_member'] == 25000.0);
        $this->assertTrue($response['penalty_fees_per'] == 0.03);
        $this->assertTrue($response['admission_fees'] == 3500.0);
        $this->assertTrue($response['contribution_frequency'] == 'monthly');
        $this->assertTrue($response['number_of_period'] == 4);

        $this->assertDatabaseHas('saving_groups', [
            'name' => 'Secure',
            'description' => 'Security of city womans',
            'target_amount_per_member' => 25000.0,
            'penalty_fees_per' => '0.03',
            'admission_fees' => 3500.0,
            'contribution_frequency' => 'monthly',
            'number_of_period' => 4,
            'total_members' => 1,
            'total_amount' => $min_amount,
            'status' => GoalStatusEnum::INPROGRESS->value,
            'type' => SavingGroupTypeEnum::CHALLENGE->value,
        ]);

        $this->assertDatabaseHas('saving_wallets', [
            'user_id' => Self::$user1['id'],
            'balance' => $min_amount + Self::$saving_wallet_balance, // Considering the groups created in the setUp function.
        ]);

        $fees = $min_amount * $internal_fees_per;

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGroup::class,
            'amount' => $min_amount,
            'fees' => $fees,
        ]);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGroup::class,
            'amount' => $min_amount,
            'fees' => 0,
        ]);

        $this->assertDatabaseHas('saving_group_members', [
            'user_id' => Self::$user1['id'],
            'is_admin' => true,
            'current_amount' => $min_amount,
            'status' => SavingGroupMemberSatusEnum::ACTIVE->value
        ]);

        $this->assertDatabaseHas('admin_wallets', [
            'balance' => $fees + Self::$admin_wallet_balance, // Considering the groups created in the setUp function.
        ]);
    }


    // /**
    //  * Test if a user can add member in normal group.(In this case, the user is trying to add himself.).
    //  *
    //  * @return void
    //  */
    // public function testCanAddMemberToNormalGroupStep1()
    // {
    //     $response = Self::$request->patch('api/groups/add-member/' . Self::$user1['id'] . '/' . Self::$group['id']);

    //     $response->assertStatus(410);

    //     $this->assertTrue($response['message'] == ErrorMessages::$ADD_MEMBER_CONFLICT);
    // }


    /**
     * Test if a user can add member in normal group.(In this case the user to add has good balance).
     *
     * @return void
     */
    public function testCanAddMemberToNormalGroup()
    {
        $internal_fees_per = TransactionUtils::getInternalFeesPer();
        $min_amount = TransactionUtils::getMinAmount();
        $wallet = Wallet::where('user_id', Self::$user2['id'])->first();
        $wallet->add(20000);

        $response = Self::$request->patch('api/groups/add-member/' . Self::$user2['id'] . '/' . Self::$normal_group['id']);

        $response->assertStatus(201);

        $this->assertTrue($response['user']['id'] == Self::$user2['id']);
        $this->assertTrue($response['saving_group_id'] == Self::$normal_group['id']);
        $this->assertTrue($response['current_amount'] == $min_amount);
        $this->assertTrue($response['is_admin'] == false);
        $this->assertTrue($response['status'] == SavingGroupMemberSatusEnum::ACTIVE->value);

        $this->assertDatabaseHas('saving_group_members', [
            'user_id' => Self::$user2['id'],
            'is_admin' => false,
            'current_amount' => $min_amount,
            'status' => SavingGroupMemberSatusEnum::ACTIVE->value
        ]);

        $this->assertDatabaseHas('saving_groups', [
            'total_members' => 2,
            'total_amount' => $min_amount * 2,
        ]);

        $fees = $min_amount * $internal_fees_per;

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGroup::class,
            'amount' => $min_amount,
            'fees' => $fees,
        ]);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGroup::class,
            'amount' => $min_amount,
            'fees' => 0,
        ]);
    }


    /**
     * Test if a user can add member in challenge group.(In this case the user to add has good balance).
     *
     * @return void
     */
    public function testCanAddMemberToChallengeGroup()
    {
        $internal_fees_per = TransactionUtils::getInternalFeesPer();
        $min_amount = 3500;
        $wallet = Wallet::where('user_id', Self::$user2['id'])->first();
        $wallet->add(20000);

        $response = Self::$request->patch('api/groups/add-member/' . Self::$user2['id'] . '/' . Self::$challenge_group['id']);

        $response->assertStatus(201);

        $this->assertTrue($response['user']['id'] == Self::$user2['id']);
        $this->assertTrue($response['saving_group_id'] == Self::$challenge_group['id']);
        $this->assertTrue($response['current_amount'] == $min_amount);
        $this->assertTrue($response['is_admin'] == false);
        $this->assertTrue($response['status'] == SavingGroupMemberSatusEnum::ACTIVE->value);

        $this->assertDatabaseHas('saving_group_members', [
            'user_id' => Self::$user2['id'],
            'is_admin' => false,
            'current_amount' => $min_amount,
            'status' => SavingGroupMemberSatusEnum::ACTIVE->value
        ]);

        $this->assertDatabaseHas('saving_groups', [
            'total_members' => 2,
            'total_amount' => $min_amount * 2,
        ]);

        $fees = $min_amount * $internal_fees_per;

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGroup::class,
            'amount' => $min_amount,
            'fees' => $fees,
        ]);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGroup::class,
            'amount' => $min_amount,
            'fees' => 0,
        ]);
    }

    // /**
    //  * Test if a user can join challenge group.
    //  *
    //  * @return void
    //  */
    // public function testCanJoinChallengeGroupStep()
    // {
    //     $wallet = Wallet::where('user_id', Self::$user2['id'])->first();
    //     $wallet->add(20000);

    //     $response = Self::$request->patch('api/groups/join/' . Self::$challenge_group['id']);

    //     $response->assertStatus(410);

    //     $this->assertTrue($response['message'] == ErrorMessages::$ERROR_MEMBER);
    // }


    /**
     * Test the progress of a saving group member contribution (In this case,
     *  the user enters a reasonable amount. That is to say,
     *  which, added with the current sum, will not exceed the final amount.).
     *
     * @return void
     */
    public function testCanUpgradeSavingGroupMemberContributionStep1()
    {
        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        $res = Self::$request->postJson('/api/groups/' . Self::$normal_group['id'] . '/update', [
            'amount' => '5000.0',
        ]);

        $res->assertStatus(201);

        $amount_without_fees = 5000.00;
        $fees = 5000.00 * $internalFeesPer;

        $this->assertTrue($res['amount'] == $amount_without_fees);
        $this->assertTrue($res['fees'] == $fees);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGroup::class,
            'amount' => $amount_without_fees,
            'fees' => $fees,
        ]);

        $this->assertDatabaseHas('saving_wallets', [
            'balance' => $amount_without_fees + Self::$saving_wallet_balance, // Considering the groups created in the setUp function.
        ]);

        $this->assertDatabaseHas('saving_groups', [
            'total_amount' => $amount_without_fees + 100, // Considering the groups created in the setUp function.
        ]);

        $this->assertDatabaseHas('saving_group_members', [
            'user_id' => Self::$user1['id'],
            'saving_group_id' => Self::$normal_group['id'],
            'current_amount' => $amount_without_fees + $fees,
        ]);
    }


    /**
     * Test the progress of a saving group member contribution (In this case,
     * the user enters an unreasonable amount.
     * That is to say which, when added with the current sum,
     * will overflow the final amount.).
     *
     * @return void
     */
    public function testCanUpgradeSavingGroupMemberContributionStep2()
    {
        $internalFeesPer = TransactionUtils::getInternalFeesPer();
        $usingFeesPer = TransactionUtils::getSavingGroupUsingFeesPer();

        $res = Self::$request->postJson('/api/groups/' . Self::$normal_group['id'] . '/update', [
            'amount' => '35000.0',
        ]);

        $currentAmount = 100.00;
        $amountToPaid = 15000.00 - $currentAmount;
        $amountToAddInWithdrawalWallet = Self::$normal_group['target_amount_per_member'] - Self::$normal_group['target_amount_per_member'] * $usingFeesPer;

        $res->assertStatus(201);

        $this->assertTrue($res['amount'] == $amountToPaid);
        $this->assertTrue($res['fees'] == $amountToPaid * $internalFeesPer);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGroup::class,
            'amount' => $amountToPaid,
            'fees' => $amountToPaid * $internalFeesPer,
            'reason' => 'Group: ' . Self::$normal_group['name'],
        ]);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGroup::class,
            'amount' => $currentAmount,
        ]);

        $this->assertDatabaseHas('transaction_wallets', [
            'wallet_type' => WithdrawalWallet::class,
        ]);

        $this->assertDatabaseHas('withdrawal_wallets', [
            'balance' => $amountToAddInWithdrawalWallet,
        ]);

        $this->assertDatabaseHas('saving_group_members', [
            'current_amount' => Self::$normal_group['target_amount_per_member'],
            'participation_status' => GoalStatusEnum::REACHED->value,
        ]);

        $this->assertDatabaseHas('saving_wallets', [
            'balance' => 0,
        ]);
    }


    /**
     * Test the progress of a saving group member contribution (In this case,
     * the user enters an unreasonable amount.
     * That is to say which, added to the current sum,
     * will equal the final amount.).
     *
     * @return void
     */
    public function testCanUpgradeSavingGroupMemberContributionStep3()
    {
        $internalFeesPer = TransactionUtils::getInternalFeesPer();
        $usingFeesPer = TransactionUtils::getSavingGroupUsingFeesPer();

        $group = Self::$request->postJson('api/groups/create-normal', [
            'name' => 'Manika',
            'description' => 'Manika of city womans',
            'deadline' => now()->addDays(3)->format('Y-m-d'),
            'target_amount_per_member' => '5000.0',
            'penalty_fees_per' => '0.03',
        ])->json();

        $res = Self::$request->postJson('/api/groups/' . $group['id'] . '/update', [
            'amount' => '5000.0',
        ]);

        $res->assertStatus(201);

        $amountToPaid = 5000.0 - 100.00;
        $amountToAddInWithdrawalWallet = $group['target_amount_per_member'] - $group['target_amount_per_member'] * $usingFeesPer;

        $res->assertStatus(201);

        $this->assertTrue($res['amount'] == $amountToPaid);
        $this->assertTrue($res['fees'] == $amountToPaid * $internalFeesPer);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGroup::class,
            'amount' => $amountToPaid,
            'fees' => $amountToPaid * $internalFeesPer,
            'reason' => 'Group: ' . $group['name'],
        ]);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGroup::class,
            'amount' => $amountToPaid,
        ]);

        $this->assertDatabaseHas('saving_group_members', [
            'current_amount' => $group['target_amount_per_member'],
            'participation_status' => GoalStatusEnum::REACHED->value,
        ]);

        $this->assertDatabaseHas('saving_wallets', [
            'balance' => 0,
        ]);

        $this->assertDatabaseHas('withdrawal_wallets', [
            'balance' => $amountToAddInWithdrawalWallet,
        ]);
    }



    /**
     * Test the progress of a challenge saving group member contribution (In this case,
     * the user enters an unreasonable amount.
     * That is to say which, added to the current sum,
     * will equal the final amount.).
     *
     * @return void
     */
    public function testCanUpgradeSavingGroupMemberContributionStep4()
    {
        $internalFeesPer = TransactionUtils::getInternalFeesPer();
        $usingFeesPer = TransactionUtils::getSavingGroupUsingFeesPer();

        $group = Self::$request->postJson('api/groups/create-challenge', [
            'name' => 'Manika',
            'description' => 'Security of city womans',
            'number_of_period' => 4,
            'contribution_frequency' => 'monthly',
            'admission_fees' => '3500.0',
            'target_amount_per_member' => '25000.0',
            'penalty_fees_per' => '0.03',
        ])->json();

        $res = Self::$request->postJson('/api/groups/' . $group['id'] . '/update', [
            'amount' => '96500.0',
        ]);

        $res->assertStatus(201);

        $amountToPaid = 25000.0 * 4 - 3500.0;
        $currentAmount = 3500.0;
        $amountToAddInWithdrawalWalletWithoutFees = $group['target_amount_per_member'] * $group['number_of_period'];
        $fees = $group['target_amount_per_member'] * $group['number_of_period'] * $usingFeesPer;
        $amountToAddInWithdrawalWalletwithFees = $amountToAddInWithdrawalWalletWithoutFees - $fees;

        $res->assertStatus(201);
        $this->assertTrue($res['amount'] == $currentAmount);
        $this->assertTrue($res['fees'] == 0);

        $this->assertDatabaseHas('transactions', [
            'reason' => 'Group: ' . $group['name'],
            'tx_type' => SavingGroup::class,
            'amount' => $amountToPaid,
            'fees' => $amountToPaid * $internalFeesPer,
        ]);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGroup::class,
            'amount' => $amountToPaid,
        ]);

        $this->assertDatabaseHas('saving_group_members', [
            'current_amount' => $amountToAddInWithdrawalWalletWithoutFees,
            'virtual_current_amount' => $amountToAddInWithdrawalWalletWithoutFees,
            'participation_status' => GoalStatusEnum::REACHED->value,
        ]);

        $this->assertDatabaseHas('saving_wallets', [
            'balance' => 0,
        ]);

        $this->assertDatabaseHas('withdrawal_wallets', [
            'balance' => $amountToAddInWithdrawalWalletwithFees,
        ]);
    }



    /**
     * Test the progress of a challenge saving group member contribution (In this case,
     * the user enters an unreasonable amount.
     * That is to say which, added to the current sum,
     * is inferior to the final amount.).
     *
     * @return void
     */
    public function testCanUpgradeSavingGroupMemberContributionStep5()
    {
        $internalFeesPer = TransactionUtils::getInternalFeesPer();

        $group = Self::$request->postJson('api/groups/create-challenge', [
            'name' => 'Manika',
            'description' => 'Security of city womans',
            'number_of_period' => 4,
            'contribution_frequency' => 'monthly',
            'admission_fees' => '3500.0',
            'target_amount_per_member' => '25000.0',
            'penalty_fees_per' => '0.03',
        ])->json();

        $res = Self::$request->postJson('/api/groups/' . $group['id'] . '/update', [
            'amount' => '6000.0',
        ]);

        $res->assertStatus(201);

        $amountToPaid = 6000.0;

        $res->assertStatus(201);

        $this->assertTrue($res['amount'] == $amountToPaid);
        $this->assertTrue($res['fees'] == $amountToPaid * $internalFeesPer);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGroup::class,
            'amount' => $amountToPaid,
            'fees' => $amountToPaid * $internalFeesPer,
            'reason' => 'Group: ' . $group['name'],
        ]);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGroup::class,
            'amount' => $amountToPaid,
        ]);

        $this->assertDatabaseHas('saving_group_members', [
            'current_amount' => $amountToPaid + $group['admission_fees'],
            'virtual_current_amount' => $amountToPaid + $group['admission_fees'],
            'participation_status' => GoalStatusEnum::INPROGRESS->value,
        ]);

        $this->assertDatabaseHas('saving_wallets', [
            'balance' => $amountToPaid + $group['admission_fees'] + Self::$saving_wallet_balance,
        ]);
    }



    /**
     * Test the cancellation of normal saving group member.
     *
     * @return void
     */
    public function testCanCancelNormalSavingGroupParticipation()
    {
        $penaltyfeesPer = Self::$normal_group['penalty_fees_per'];

        $res = Self::$request->patch('/api/groups/' . Self::$normal_group['id'] . '/normal-cancel');

        $res->assertStatus(201);

        $amount = 100;
        $penalty = $amount * $penaltyfeesPer;

        $this->assertTrue($res['amount'] == $amount);
        $this->assertTrue($res['fees'] == 0);

        $this->assertDatabaseHas('saving_group_members', [
            'current_amount' => $amount,
            'participation_status' => GoalStatusEnum::CANCELLED->value,
        ]);

        $this->assertDatabaseHas('withdrawal_wallets', [
            'balance' => $amount - $penalty,
        ]);

        $this->assertDatabaseHas('admin_wallets', [
            'balance' => $penalty + Self::$admin_wallet_balance, // Considering the groups created in the setUp function.
        ]);

        $this->assertDatabaseHas('saving_wallets', [
            'balance' => 0,
        ]);
    }


    /**
     * Test the cancellation of challenge saving group member (In this step, The money redistributed does not end any member's contribution).
     *
     * @return void
     */
    public function testCanCancelChallengeSavingGroupParticipationStep1()
    {
        $wallet = Wallet::where('user_id', Self::$user2['id'])->first();
        $wallet->add(20000);

        Self::$request->patch('api/groups/add-member/' . Self::$user2['id'] . '/' . Self::$challenge_group['id']);

        $res = Self::$request->patch('/api/groups/' . Self::$challenge_group['id'] . '/challenge-cancel');

        $res->assertStatus(201);

        $amount = 3500.0;

        $this->assertTrue($res['amount'] == $amount);
        $this->assertTrue($res['fees'] == 0);

        $this->assertDatabaseHas('saving_group_members', [
            'current_amount' => $amount,
            'participation_status' => GoalStatusEnum::CANCELLED->value,
            'status' => SavingGroupMemberSatusEnum::LEFT->value,
        ]);

        $this->assertDatabaseHas('saving_group_members', [
            'current_amount' => $amount * 2,
            'virtual_current_amount' => $amount * 2,
            'participation_status' => GoalStatusEnum::INPROGRESS->value,
        ]);
    }


    /**
     * Test the cancellation of challenge saving group member (In this step, The money redistributed does not end the members' contribution).
     *
     * @return void
     */
    public function testCanCancelChallengeSavingGroupParticipationStep2()
    {
        $internalFeesPer = TransactionUtils::getInternalFeesPer();
        $usingFeesPer = TransactionUtils::getSavingGroupUsingFeesPer();

        $wallet = Wallet::where('user_id', Self::$user2['id'])->first();
        $wallet->add(20000);

        $res = Self::$request->postJson('/api/groups/' . Self::$challenge_group['id'] . '/update', [
            'amount' => '96000.0',
        ]);

        Self::$request->patch('api/groups/add-member/' . Self::$user2['id'] . '/' . Self::$challenge_group['id']);

        $addAmount = 10000.0;
        $user = User::find(Self::$user2['id']);
        $user->savingWallet->add($addAmount);

        $member = SavingGroupMember::where('user_id', $user->id)
            ->where('saving_group_id', Self::$challenge_group['id'])
            ->first();

        $member->current_amount += $addAmount;
        $member->virtual_current_amount += $addAmount;
        $member->save();

        $adminWallet = AdminWallet::first();
        $adminWallet->balance = 0;
        $adminWallet->save();

        $res = Self::$request->patch('/api/groups/' . Self::$challenge_group['id'] . '/challenge-cancel');

        $res->assertStatus(201);

        $amountShare = 99500.0;
        $amountPaid = 100000.0 - (3500.0 + $addAmount);
        $amountToRemoveOnSavingWallet = 3500.0 + $addAmount;
        $finalAmount = Self::$challenge_group['target_amount_per_member'] * Self::$challenge_group['number_of_period'];

        $this->assertTrue($res['amount'] == $amountShare);
        $this->assertTrue($res['fees'] == 0);

        $this->assertDatabaseHas('saving_group_members', [
            'current_amount' => $amountShare,
            'participation_status' => GoalStatusEnum::CANCELLED->value,
            'status' => SavingGroupMemberSatusEnum::LEFT->value,
        ]);

        $this->assertDatabaseHas('saving_group_members', [
            'current_amount' => $finalAmount,
            'virtual_current_amount' => $finalAmount,
            'participation_status' => GoalStatusEnum::REACHED->value,
        ]);

        $this->assertDatabaseHas('transactions', [
            'tx_type' => SavingGoal::class,
            'amount' => $amountToRemoveOnSavingWallet,
            'reason' => 'Group: ' . Self::$challenge_group['name'],
        ]);

        $this->assertDatabaseHas('admin_wallets', [
            'balance' => $amountShare - $amountPaid + $amountPaid * $internalFeesPer,
        ]);

        $this->assertDatabaseHas('withdrawal_wallets', [
            'balance' => $finalAmount - $finalAmount * $usingFeesPer,
        ]);
    }


    /**
     * Test challenge group status updater (In this step all members are disqualified).
     *
     * @return void
     */
    public function testChallengeGroupStatusUpdaterStep1()
    {
        $internal_fees_per = TransactionUtils::getInternalFeesPer();
        $min_amount = 3500.0;
        $wallet = Wallet::where('user_id', Self::$user2['id'])->first();
        $wallet->add(20000);

        $group = SavingGroup::find(Self::$challenge_group['id']);
        $group->current_period_end_date = now()->format('Y-m-d');
        $group->save();

        Self::$request->patch('api/groups/add-member/' . Self::$user2['id'] . '/' . Self::$challenge_group['id']);

        $adminWallet = AdminWallet::first();
        $adminWallet->balance = 0;
        $adminWallet->save();

        $this->artisan('app:challenge-saving-group-status-updater');

        $this->assertDatabaseHas('saving_groups', [
            'total_members' => 2,
            'total_amount' => $min_amount * 2,
            'status' => SavingGroupStatusEnum::END->value,
            'type' => SavingGroupTypeEnum::CHALLENGE->value
        ]);

        $this->assertDatabaseHas('saving_group_members', [
            'user_id' => Self::$user2['id'],
            'is_admin' => false,
            'current_amount' => $min_amount,
            'participation_status' => GoalStatusEnum::NO_REACHED->value
        ]);

        $this->assertDatabaseHas('saving_group_members', [
            'user_id' => Self::$user1['id'],
            'is_admin' => true,
            'current_amount' => $min_amount,
            'participation_status' => GoalStatusEnum::NO_REACHED->value
        ]);

        $fees = $min_amount * $internal_fees_per;

        $this->assertDatabaseHas('transactions', [
            'user_id' => Self::$user1['id'],
            'tx_type' => SavingGroup::class,
            'amount' => $min_amount,
            'reason' => 'Cancellation with penalty of the savings group.',
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => Self::$user2['id'],
            'tx_type' => SavingGroup::class,
            'amount' => $min_amount,
            'reason' => 'Cancellation with penalty of the savings group.',
        ]);

        $this->assertDatabaseHas('admin_wallets', [
            'balance' => $min_amount * 2,
        ]);
    }


    /**
     * Test challenge group status updater (In this step all members aren't disqualified).
     *
     * @return void
     */
    public function testChallengeGroupStatusUpdaterStep2()
    {
        $min_amount = 3500.0;
        $wallet = Wallet::where('user_id', Self::$user2['id'])->first();
        $wallet->add(20000);

        $group = SavingGroup::find(Self::$challenge_group['id']);
        $group->current_period_end_date = now()->format('Y-m-d');
        $group->save();

        Self::$request->postJson('/api/groups/' . Self::$challenge_group['id'] . '/update', [
            'amount' => '30000.0',
        ]);

        $amountAdd = 30000.0;

        Self::$request->patch('api/groups/add-member/' . Self::$user2['id'] . '/' . Self::$challenge_group['id']);

        $adminWallet = AdminWallet::first();
        $adminWallet->balance = 0;
        $adminWallet->save();

        $this->artisan('app:challenge-saving-group-status-updater');

        $this->assertDatabaseHas('saving_groups', [
            'total_members' => 2,
            'total_amount' => $min_amount * 2 + $amountAdd,
            'status' => SavingGroupStatusEnum::INPROGRESS->value,
            'type' => SavingGroupTypeEnum::CHALLENGE->value,
            'current_period' => 2,
            'current_period_end_date' => DateUtils::addPeriodes($group->contribution_frequency, $group->current_period_end_date)
        ]);

        $this->assertDatabaseHas('saving_group_members', [
            'user_id' => Self::$user2['id'],
            'is_admin' => false,
            'current_amount' => $min_amount,
            'participation_status' => GoalStatusEnum::NO_REACHED->value
        ]);

        $this->assertDatabaseHas('saving_group_members', [
            'user_id' => Self::$user1['id'],
            'is_admin' => true,
            'current_amount' => $min_amount * 2 + $amountAdd,
            'participation_status' => GoalStatusEnum::INPROGRESS->value
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => Self::$user2['id'],
            'tx_type' => SavingGroup::class,
            'amount' => $min_amount,
            'reason' => 'Cancellation with penalty of the savings group.',
        ]);

        $this->assertDatabaseHas('admin_wallets', [
            'balance' => 0,
        ]);
    }


    /**
     * Test normal group status updater.
     *
     * @return void
     */
    public function testNormalGroupStatusUpdater()
    {
        $min_amount = TransactionUtils::getMinAmount();
        $wallet = Wallet::where('user_id', Self::$user2['id'])->first();
        $wallet->add(20000);

        $group = SavingGroup::find(Self::$normal_group['id']);
        $group->deadline = now()->format('Y-m-d');
        $group->save();

        Self::$request->postJson('/api/groups/' . Self::$normal_group['id'] . '/update', [
            'amount' => '30000.0',
        ]);

        Self::$request->patch('api/groups/add-member/' . Self::$user2['id'] . '/' . Self::$normal_group['id']);

        $adminWallet = AdminWallet::first();
        $adminWallet->balance = 0;
        $adminWallet->save();

        $this->artisan('app:normal-saving-group-status-updater');

        $this->assertDatabaseHas('saving_groups', [
            'total_members' => 2,
            'status' => SavingGroupStatusEnum::END->value,
            'type' => SavingGroupTypeEnum::NORMAL->value,
        ]);

        $this->assertDatabaseHas('saving_group_members', [
            'user_id' => Self::$user2['id'],
            'is_admin' => false,
            'current_amount' => $min_amount,
            'participation_status' => GoalStatusEnum::NO_REACHED->value
        ]);

        $this->assertDatabaseHas('saving_group_members', [
            'user_id' => Self::$user1['id'],
            'is_admin' => true,
            'current_amount' => $group->target_amount_per_member,
            'participation_status' => GoalStatusEnum::REACHED->value
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => Self::$user2['id'],
            'tx_type' => SavingGroup::class,
            'amount' => $min_amount,
            'reason' => 'Cancellation with penalty of the savings group.',
        ]);

        $this->assertDatabaseHas('admin_wallets', [
            'balance' => $min_amount * $group->penalty_fees_per,
        ]);
    }
}
