<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Referral;
use Illuminate\Http\Request;
use App\Http\ResponseWrapper;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Notifications\SignupActivate;

class AuthController extends Controller
{
    //
    /**
     * Create a new user
     *
     * @param Illuminate\Http\Request
     *
     * @return \Illuminate\Http\JsonResponse
     * @unauthenticated
     */
    public function signup(Request $req)
    {
        $req->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone' => "required|string",
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed',
            'referral_code' => 'nullable|string|exists:referrals,code',
        ]);

        $user = new User([
            'first_name' => $req->post('first_name'),
            'last_name' => $req->post('last_name'),
            'phone' => $req->post("phone"),
            "country" => $req->post("country") ?? 'Cameroon',
            'password' => bcrypt($req->password),
            'email' => $req->post("email"),
            'activation_token' => rand(10000, 99999),
        ]);


        if ($req->has('referral_code')) {
            $referral = Referral::where('code', $req->referral_code)->first();
            abort_unless($referral, 401, "The referral code is not correct.");

            $user->referrer_id = $referral->user->id;
        }

        $user->save();

        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;

        //In case the remember me exists, we use carbon to make the token expires one week later
        $token->expires_at = $req->remember_me? Carbon::now()->addWeeks(52) : Carbon::now()->addHours(24);

        $token->save();

        if (env('APP_ENV') == "local") {
            // error_log("\n\nYOUR ACTIVATION CODE IS");
            // error_log($user->activation_token);

            Log::info("\n\nYOUR ACTIVATION CODE IS");
            Log::info($user->activation_token);
        } else {
            $user->notify(new SignupActivate($user));
        }

        $data = [
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse($tokenResult->token->expires_at)->toDateTimeString(),
            // 'status' => 201,
            'message' => 'User created successfully.',
            'user' => new UserResource($user)
        ];

        return response()->json($data, 201);
    }

    /**
     * Login a new user into the application
     *
     * @param Illuminate\Http\Request
     *
     * @return JSON
     *
     *  @unauthenticated
     */
    public function login(Request $req)
    {
        $req->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'present',
        ]);

        $credentials = request(['email', 'password']);

        abort_unless(Auth::attempt($credentials), 401, "The email or the password is not correct.");

        $user = $req->user();

        abort_unless(
            $user->email_verified_at,
            412,
            "Please confirm your email before logging in. Email not received? use the link at the bottom to resend the email",
        );

        abort_if($user->disabled, 403, "Your account has been suspended, please contact us for more info");

        //Retrieve a token for the user
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;

        //In case the remember me exists, we use carbon to make the token expires one week later
        if (null != $req->remember_me) {
            $token->expires_at = Carbon::now()->addDay();
        } else {
            $token->expires_at = Carbon::now()->addHour(10);
        }

        $token->save();

        return [
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
            'expires_at' => Carbon::parse($tokenResult->token->expires_at)->toDateTimeString()
        ];
    }

    /**
     * Logout a user from the application
     *
     * @param Illuminate\Http\Request
     * @return JSON payload
     */
    public function logout(Request $req)
    {
        $req->user()->token()->revoke();
        return response()->noContent();
    }


    /**
     * Activation link of the create account
     * @param string token
     *  @unauthenticated
     */
    public function signupActivate($email, $token)
    {
        $user = User::where('email', $email)->where('activation_token', $token)->first();

        // If there'setup no such a user return invalid token
        if (!$user) {
            return ResponseWrapper::sendResponse("Activation token expired or invalid $token", [], 403);
        }

        $user->email_verified_at = Carbon::now();
        $user->activation_token = '';

        $user->save();

        return new UserResource($user);
    }

    public function user()
    {
        return new UserResource(User::findOrFail(Auth::id()));
    }


    /***
     *  @unauthenticated
     */
    public function resendRegisterEmail(Request $req)
    {
        $req->validate(['email' => 'required|email']);
        $user = User::whereEmail($req->email)->first();

        if ($user) {
            if ($user->email_verified_at) {
                return response("Account already activated !", 208);
            } else {
                $user->notify(new SignupActivate());
                $message = "Confirmation email has been successfully sent to $req->email.\n If you don\'t see the email, please check your spam";
                return response($message, 202);
            }
        } else {
            abort(404, "No user found with that email: $req->email");
        }
    }

    public function deleteAccount(Request $req) {
        $user = $req->user()->delete();
        return response()->json("User removed", 20);
    }


    public function updatePassword(Request $request) {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|different:current_password',
            'confirm_password' => 'required|same:new_password',
        ]);

        /**
         * @var User
         */
        $user = auth()->user();

        // Verify the current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json('The current password is incorrect.', 401);
        }

        // Update the user's password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json('Password updated successfully.');
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'country' => 'required|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'preferred_lang' => 'nullable|string|size:2',
        ]);

        $user = $request->user();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->country = $request->country;
        $user->preferred_lang = $request->preferred_lang;

        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $avatarName = $user->id . '_' . time() . '.' . $avatar->getClientOriginalExtension();
            $avatar->storeAs('avatars', $avatarName);
            $user->avatar = $avatarName;
        }
        $user->save();

        return response()->json("Profile updated", 200);
    }

}
