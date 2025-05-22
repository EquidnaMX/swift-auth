<?php

namespace Teleurban\SwiftAuth\Http\Controllers;

use Teleurban\SwiftAuth\Traits\SelectiveRender;
use Teleurban\SwiftAuth\Models\User;
use Teleurban\SwiftAuth\Models\Role;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Teleurban\SwiftAuth\Facades\SwiftAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Inertia\Response;
use Illuminate\Support\Facades\Config;

/**
 * Class UserController
 * 
 * Manages user-related functionalities such as registration, profile display, and user management (create, update, delete).
 *
 * @package Teleurban\SwiftAuth\Http\Controllers
 */
class UserController extends Controller
{
    use SelectiveRender;

    /**
     * Display a list of users with search functionality.
     *
     * @param Request $request
     * @return View|Response
     */
    public function index(Request $request): View|Response
    {
        $users = User::search($request->get("search"))
            ->paginate(10);

        return $this->render('swift-auth::user.index', 'user/Index', [
            'users' => $users,
            'actions' => Config::get('swift-auth.actions'),
        ]);
    }

    /**
     * Show the user registration form.
     *
     * @param Request $request
     * @return View|Response
     */
    public function register(Request $request): View|Response
    {
        return $this->render('swift-auth::register', 'Register');
    }

    /**
     * Show the form to create a new user.
     *
     * @param Request $request
     * @return View|Response
     */
    public function create(Request $request): View|Response
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

    /**
     * Store a new user in the database.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:Users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|exists:Roles,id_role',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->roles()->attach($request->role);

        if (SwiftAuth::check()) {
            return redirect()->route('swift-auth.users.index')->with('success', 'Registration successful.');
        }

        SwiftAuth::login($user);

        return redirect()->route('swift-auth.users.index')->with('success', 'Registration successful.');
    }
    /**
     * Display the details of a specific user.
     *
     * @param Request $request
     * @param string $id_user
     * @return View|Response
     */
    public function show(Request $request, string $id_user): View|Response
    {
        $user = User::findOrFail($id_user);
        return $this->render('swift-auth::user.show', 'user/Show', ['user' => $user]);
    }

    /**
     * Show the form to edit the user's details.
     *
     * @param Request $request
     * @param string $id_user
     * @return View|Response
     */
    public function edit(Request $request, string $id_user): View|Response
    {
        $user = User::findOrFail($id_user);
        return $this->render('swift-auth::user.edit', 'user/Edit', ['user' => $user]);
    }

    /**
     * Update the user's information.
     *
     * @param Request $request
     * @param string $id_user
     * @return RedirectResponse
     */
    public function update(Request $request, string $id_user): RedirectResponse
    {
        $user = User::findOrFail($id_user);

        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:Users,email,' . $id_user,
            ]
        );

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user->update([
            'name' => $request->name ?? $user->name,
            'email' => $request->email ?? $user->email,
        ]);

        $user->roles()->sync($request->roles);

        return redirect()
            ->route('swift-auth.users.show', $id_user)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Delete a user from the database.
     *
     * @param Request $request
     * @param string $id_user
     * @return RedirectResponse
     */
    public function destroy(Request $request, string $id_user): RedirectResponse
    {
        if (SwiftAuth::id() === (int) $id_user) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user = User::findOrFail($id_user);
        $user->delete();

        return redirect()
            ->route('swift-auth.users.index')
            ->with('success', 'User successfully deleted.');
    }
}
