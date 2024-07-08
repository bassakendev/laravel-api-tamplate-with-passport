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

    public function children($id)
    {
        $user = User::find($id);
        $users = User::where('referrer', $user->referral_code)->latest('updated_at')->paginate(100);
        return view('pages.users.children', compact('users', 'user'));
    }

    public function show($id)
    {
        $user = User::find($id);
        return view('pages.users.show', compact('user'));
    }

    public function edite($id)
    {
        $user = User::find($id);
        return view('pages.users.edite', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:8'],
        ]);

        //Get this user by id
        $user = User::find($id);

        //Update credentials
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->save();

        return redirect()->route('show.users', $id)->with('success', 'Updated Successfully');
    }

    public function delete($id)
    {
        User::destroy($id);
        return redirect()->route('all.users')->with('success', 'Deleted Successfully');
    }
}
