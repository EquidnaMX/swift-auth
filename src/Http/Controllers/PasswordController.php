<?php

namespace Teleurban\SwiftAuth\Http\Controllers;

use Teleurban\SwiftAuth\Traits\SelectiveRender;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Response;

/**
 * Class PasswordController
 *
 * Handles all password reset-related processes, including:
 * - Displaying reset request and reset forms
 * - Sending password reset links
 * - Resetting the user's password
 * 
 * @package Teleurban\SwiftAuth\Http\Controllers
 */
class PasswordController extends Controller
{
    use SelectiveRender;

    /**
     * Show the password reset request form.
     *
     * @param Request $request
     * @return View|Response
     */
    public function showResetForm(Request $request): View|Response
    {
        return $this->render('swift-auth::password.email', 'password/Request');
    }

    /**
     * Show the new password form.
     *
     * @param Request $request
     * @return View|Response
     */
    public function showNewPasswordForm(Request $request): View|Response
    {
        return $this->render('swift-auth::password.reset', 'password/Reset');
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
