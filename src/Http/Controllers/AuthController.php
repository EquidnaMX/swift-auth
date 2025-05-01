<?php

namespace Teleurban\SwiftAuth\Http\Controllers;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Teleurban\SwiftAuth\Traits\SelectiveRender;

class AuthController extends Controller
{
    use SelectiveRender;

    public function showLoginForm(Request $request)
    {
        return $this->render('swift-auth::login', 'Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()
                ->to(
                    config('swift-auth.success_url')
                )->with('success', 'Login successful.');
        }

        return back()->with('error', 'Invalid credentials.');
    }

    public function showResetForm(Request $request)
    {
        return $this->render('swift-auth::password.email', 'ForgotPassword');
    }

    public function showNewPasswordForm(Request $request)
    {
        return $this->render('swift-auth::password.reset', 'ResetPassword');
    }



    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('swift-auth.login')->with('success', 'Logged out successfully.');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:6|confirmed',
            'token' => 'required',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('swift-auth.login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
