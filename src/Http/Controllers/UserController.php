<?php

namespace Teleurban\SwiftAuth\Http\Controllers;

use Teleurban\SwiftAuth\Traits\SelectiveRender;
use Teleurban\SwiftAuth\Models\User;
use Teleurban\SwiftAuth\Models\Role;
use Illuminate\Routing\Controller;

class UserController extends Controller
{
    use SelectiveRender;

    public function index(Request $request)
    {
        $users = User::search($request->get("search"))
            ->paginate(10);

        return $this->render('swift-auth::user.index', 'user/Index', ['users' => $users]);
    }

    public function register(Request $request)
    {
        return $this->render('swift-auth::register', 'Register');
    }

    public function create(Request $request)
    {
        $roles = Role::orderBy('name')->get();

        return $this->render(
            'swift-auth::user.create',
            'user/Create',
            [
                'roles' => $roles
            ]
        );
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if (Auth::check()) {
            return redirect()->route('swift-auth.users.index')->with('success', 'Registration successful.');
        }

        Auth::login($user);

        return redirect()->route('swift-auth.users.index')->with('success', 'Registration successful.');
    }

    public function show(Request $request, string $id_user)
    {
        $user = User::findOrFail($id_user);
        return $this->render('swift-auth::user.show', 'user/Show', ['user' => $user]);
    }

    public function edit(Request $request, string $id_user)
    {
        $user = User::findOrFail($id_user);
        return $this->render('swift-auth::user.edit', 'user/Edit', ['user' => $user]);
    }

    public function update(Request $request, $id_user)
    {
        $user = User::findOrFail($id_user);

        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id_user,
            ]
        );

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user->update(
            [
                'name' => $request->name ?? $user->name,
                'email' => $request->email ?? $user->email,
            ]
        );

        $user->roles()->sync($request->roles);

        return redirect()
            ->route('swift-auth.users.show', $id_user)
            ->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, $id_user)
    {
        if (Auth::id() === (int) $id_user) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user = User::findOrFail($id_user);
        $user->delete();

        return redirect()
            ->route('swift-auth.users.index')
            ->with('success', 'User successfully deleted.');
    }
}
