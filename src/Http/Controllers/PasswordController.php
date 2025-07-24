<?php

namespace Teleurban\SwiftAuth\Http\Controllers;

use Teleurban\SwiftAuth\Models\PasswordResetToken;
use Teleurban\SwiftAuth\Mail\PasswordResetMail;
use Teleurban\SwiftAuth\Traits\SelectiveRender;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Response;
use Mail;
use Str;

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
    public function showRequestForm(Request $request): View|Response
    {
        return $this->render('swift-auth::password.email', 'password/Request');
    }

    /**
     * Send a password reset link to the user.
     *
     * @param Request $request
     * @return View|Response
     */
    public function sendResetLink(Request $request): View|Response
    {
        $request->validate(['email' => 'required|email|exists:Users,email']);

        $email = $request->email;
        $token = Str::random(64);

        PasswordResetToken::updateOrCreate(
            ['email' => $email],
            [
                'token' => hash('sha256', $token),
                'created_at' => now(),
            ]
        );

        Mail::to($email)->send(new PasswordResetMail(email: $email, token: $token));

        return $this->render('swift-auth::password.reset', 'password/RequestSent');
    }

    /**
     * Show the new password form.
     *
     * @param Request $request
     * @return View|Response
     */
    public function showResetForm(Request $request): View|Response
    {
        return $this->render('swift-auth::password.reset', 'password/Reset');
    }

    /**
     * Update the user's password.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function resetPassword(Request $request): View|Response
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

        return $this->render('swift-auth::login', 'Login');
    }
}
