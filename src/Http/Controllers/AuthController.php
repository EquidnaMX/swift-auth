<?php

namespace Teleurban\SwiftAuth\Http\Controllers;

use Teleurban\SwiftAuth\Traits\SelectiveRender;
use Teleurban\SwiftAuth\Facades\SwiftAuth;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Teleurban\SwiftAuth\Models\User;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Response;

/**
 * Class AuthController
 * 
 * Handles the authentication processes, including login, logout, password reset, and view rendering.
 *
 * @package Teleurban\SwiftAuth\Http\Controllers
 */
class AuthController extends Controller
{
    use SelectiveRender;

    /**
     * Show the login form view.
     *
     * @param Request $request
     * @return View|Response
     */
    public function showLoginForm(Request $request): View|Response
    {
        return $this->render('swift-auth::login', 'Login');
    }

    /**
     * Handle login request.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            SwiftAuth::login($user);
            $request->session()->regenerate();

            return redirect()
                ->to(Config::get('swift-auth.success_url'))
                ->with('success', 'Login successful.');
        }

        return back()->with('error', 'Invalid credentials.');
    }

    /**
     * Handle the logout process.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        SwiftAuth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('swift-auth.login.form')->with('success', 'Logged out successfully.');
    }
}
