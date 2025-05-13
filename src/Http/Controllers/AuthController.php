<?php

namespace Teleurban\SwiftAuth\Http\Controllers;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Teleurban\SwiftAuth\Facades\SwiftAuth;
use Teleurban\SwiftAuth\Models\User;
use Teleurban\SwiftAuth\Traits\SelectiveRender;
use Illuminate\Http\RedirectResponse;
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
     * Show the password reset request form.
     *
     * @param Request $request
     * @return View|Response
     */
    public function showResetForm(Request $request): View|Response
    {
        return $this->render('swift-auth::password.email', 'ForgotPassword');
    }

    /**
     * Show the new password form.
     *
     * @param Request $request
     * @return View|Response
     */
    public function showNewPasswordForm(Request $request): View|Response
    {
        return $this->render('swift-auth::password.reset', 'ResetPassword');
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

    /**
     * Send a password reset link to the user.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email|exists:Users,email']);

        $status = Password::broker()->sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Update the user's password.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|exists:Users,email',
            'password' => 'required|min:6|confirmed',
            'token' => 'required',
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('swift-auth.login.form')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
