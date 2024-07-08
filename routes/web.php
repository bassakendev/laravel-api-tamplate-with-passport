<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\PubController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SaleCopyController;
use App\Http\Controllers\Admin\ExamsResultController;
use App\Http\Controllers\Admin\VideoCourseController;
use App\Http\Controllers\Admin\QuestionAndSolutionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

// Route::get('/home', function() {
//     return view('home');
// })->name('home')->middleware('auth');

Route::prefix('/admin')->controller(AdminController::class)->middleware('auth')->group(function () {
    Route::get('/', 'index')->name(('home'));

    Route::prefix('/users')->controller(UserController::class)->group(function () {
        Route::get('/', 'index')->name(('all.users'));
        Route::get('/show/{id}',  'show')->name(('show.users'));
        Route::get('/children/{id}',  'children')->name(('children.users'));
        Route::get('/edite/{id}', 'edite')->name(('edite.users'));
        Route::post('/update/{id}', 'update')->name(('update.users'));
        Route::get('/delete/{id}', 'delete')->name(('delete.users'));
    });
});
