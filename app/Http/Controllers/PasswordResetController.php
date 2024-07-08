<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\ResponseWrapper;
use App\Models\PasswordReset;
use App\Http\Controllers\Controller;
use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;

class PasswordResetController extends Controller
{

    /**
     *  Create a new token for the password reset
     * @param Illuminate\Http\Request
     * @return string message
     */

    public function create(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => rand(100000, 999999),
                'created_at' => now(),
            ]
        );

        if ($user && $passwordReset) {
            $user->notify(new PasswordResetRequest($passwordReset->token));
        }

        return response()->noContent();
    }

    /**
     * Find token password reset
     *
     * @param  [string] $token
     * @return [string] message
     * @return [json] passwordReset object
     */
    public function find($token)
    {

        $passwordReset = PasswordReset::where('token', $token)
        ->first();

        if (!$passwordReset) {
            return ResponseWrapper::sendResponse("This password reset token is invalid.", [], 422);
        }

        if (Carbon::parse($passwordReset->updated_at)->addMinutes(720)->isPast()) {
            $passwordReset->delete();
            return ResponseWrapper::sendResponse("The password reset link has expired.", [], 408);
        }

        $passwordReset->status = "success";

        return ResponseWrapper::sendResponse("", [$passwordReset], 200);
    }

    /**
     * Reset password
     *
     * @param  string email
     * @param  string password
     * @param  string password_confirmation
     * @param  string token
     * @return string message
     * @return json user object
     */
    public function reset(Request $request)
    {

        $request->validate([
            'email' => '',
            'password' => 'required|string|confirmed ',
            'token' => ''
        ]);

        $passwordReset = PasswordReset::query()
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        abort_if(!$passwordReset, 408, "This password reset token is invalid.");

        $user = User::where('email', $request->email)->firstOrFail();
        $user->password = bcrypt($request->password);
        $user->save();

        $passwordReset->where('email', $request->email)->delete();

        $user->notify(new PasswordResetSuccess($passwordReset));

        return response()->noContent();
    }
}
