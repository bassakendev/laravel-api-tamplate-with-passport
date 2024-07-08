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


//Pub route
Route::prefix("pubs")->middleware("auth:api")->controller(UserPubController::class)->group(function () {
    Route::get('/active-pub', 'active');
    Route::post('/save-view', 'onView');
    Route::post('/save-click', 'onClick');
});
