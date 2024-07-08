<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $users = User::latest('updated_at')->paginate(100);
        return view('pages.users.index', compact('users'));
    }

    public function children($user_id)
    {
        $user = User::find($user_id);
        $users = User::where('referrer', $user->referral_code)->latest('updated_at')->paginate(100);
        return view('pages.users.children', compact('users', 'user'));
    }

    public function show($id)
    {
        $user = User::find($id);
        return view('pages.users.show', compact('user'));
    }

    public function updateStatus($id)
    {
        //Get this user by id
        $user = User::find($id);

        return redirect()->route('show.users', $id)->with('success', 'Updated Successfully');
    }
}
