<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserPubController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\Transaction\LoanController;
use App\Http\Controllers\Transaction\DepositController;
use App\Http\Controllers\Transaction\TransferController;
use App\Http\Controllers\Transaction\SavingGoalController;
use App\Http\Controllers\transaction\SavingGroupController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:passport')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/', function () {
    $payload = [
        'version' => 'v1',
        'vendor' => 'Ficedu',
        'message' => 'Hello welcome to our v1!'
    ];
    return response()->json($payload);
});


Route::group(['prefix' => 'auth'], function () {
    Route::post('forgotpassword', 'App\Http\Controllers\PasswordResetController@create');
    Route::get('find/{token}', 'App\Http\Controllers\PasswordResetController@find');
    Route::post('resetpassword', 'App\Http\Controllers\PasswordResetController@reset');
});



Route::group(["prefix" => "auth", "middleware" => "api"], function () {
    Route::post('login', "App\Http\Controllers\AuthController@login");
    Route::post('register', 'App\Http\Controllers\AuthController@signup');

    Route::get('register/activate/{email}/{token}', 'App\Http\Controllers\AuthController@signupActivate');

    Route::group(["middleware" => "auth:api"], function () {
        Route::get('logout', "App\Http\Controllers\AuthController@logout");
        Route::get('user', 'App\Http\Controllers\AuthController@user');
    });

    Route::post('resendcode', 'App\Http\Controllers\AuthController@resendRegisterEmail');
});

Route::prefix("private")->middleware("auth:api")->controller(AuthController::class)->group(function () {
    Route::delete('/delete-my-account', 'deleteAccount');
    Route::patch('/change-password', 'updatePassword');
    Route::patch('/update-profile', 'updateProfile');
});

//Savings goal routes
Route::prefix('savings')->middleware("auth:api")->controller(SavingGoalController::class)->group(function () {
    Route::post('/add', 'create');
    Route::get('/list', 'list');
    Route::get('{id}/details', 'details');
    Route::post('{id}/update', 'update');
    Route::patch('{id}/cancel', 'cancel');
});

//Loan routes
Route::prefix('loans')->middleware("auth:api")->controller(LoanController::class)->group(function () {
    Route::post('/{savingGoalId}/ask', 'makeLoanWithSavingGoalAsCollateral');
    Route::get('list', 'list');
    Route::get('{id}/details', 'details');
    Route::post('{id}/refund', 'update');
});

//Setting routes
Route::prefix('settings')->middleware("api")->controller(SettingController::class)->group(function () {
    Route::get('/get', 'get');
});

//Saving group routes
Route::prefix('groups')->middleware("auth:api")->controller(SavingGroupController::class)->group(function () {
    Route::get('list', 'list');
    Route::post('create-normal', 'newNormalSavingGroup');
    Route::post('create-challenge', 'newChallengeSavingGroup');
    Route::patch('add-member/{userToAddId}/{groupId}', 'addMember');
    Route::patch('{groupId}/normal-cancel', 'cancelNormalParticipation');
    Route::patch('{groupId}/challenge-cancel', 'cancelCallengeParticipation');
    Route::post('{groupId}/update', 'upgradeContribution');
    Route::get('{groupId}/details', 'details');
    Route::patch('join/{groupId}', 'joinChallengeGroup');
});

//Dashboard routes
Route::prefix('dashboard')->middleware("auth:api")->controller(UserDashboardController::class)->group(function () {
    Route::get('common', 'common');
});

//Transfer routes
Route::prefix('transfer')->middleware("auth:api")->controller(TransferController::class)->group(function () {
    Route::get('list', 'list');
    Route::post('', 'transferTo');
});

//Deposite routes
Route::prefix('deposit')->middleware("auth:api")->controller(DepositController::class)->group(function () {
    Route::get('list', 'list');
    Route::post('',
        'deposit'
    );
});

//Deposite routes
Route::prefix('campay/ipn')->middleware("api")->controller(TransactionController::class)->group(function () {
    Route::get('', 'update');
});

//Pub route
Route::prefix("pubs")->middleware("auth:api")->controller(UserPubController::class)->group(function () {
    Route::get('/active-pub', 'active');
    Route::post('/save-view', 'onView');
    Route::post('/save-click', 'onClick');
});
