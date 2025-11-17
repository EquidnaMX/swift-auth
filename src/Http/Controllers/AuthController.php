<?php

/**
 * Exposes SwiftAuth login/logout flows for Laravel apps.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwifthAuth\Http\Controllers
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth
 */

namespace Equidna\SwifthAuth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Equidna\Toolkit\Exceptions\UnauthorizedException;
use Equidna\Toolkit\Helpers\ResponseHelper;
use Inertia\Response;
use Equidna\SwifthAuth\Facades\SwiftAuth;
use Equidna\SwifthAuth\Models\User;
use Equidna\SwifthAuth\Traits\SelectiveRender;

/**
 * Orchestrates SwiftAuth authentication flows across login/logout endpoints.
 *
 * Presents blade or Inertia views as needed and emits context-aware toolkit responses.
 */
class AuthController extends Controller
{
    use SelectiveRender;

    /**
     * Shows the login form view.
     *
     * @param  Request       $request  HTTP request with context info.
     * @return View|Response           Blade or Inertia response.
     */
    public function showLoginForm(Request $request): View|Response
    {
        return $this->render(
            'swift-auth::login',
            'Login',
        );
    }

    /**
     * Authenticates the user using SwiftAuth.
     *
     * @param  Request                   $request  HTTP request with credentials.
     * @return RedirectResponse|JsonResponse       Context-aware success response.
     * @throws UnauthorizedException               When credentials are invalid.
     */
    public function login(Request $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw new UnauthorizedException('Invalid credentials.');
        }

        SwiftAuth::login($user);
        $request->session()->regenerate();

        return ResponseHelper::success(
            message: 'Login successful.',
            data: [
                'user_id' => $user->getKey(),
            ],
            forward_url: Config::get('swift-auth.success_url'),
        );
    }

    /**
     * Logs out the current user and clears the session.
     *
     * @param  Request                   $request  HTTP request carrying the session.
     * @return RedirectResponse|JsonResponse       Context-aware success response.
     */
    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        SwiftAuth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ResponseHelper::success(
            message: 'Logged out successfully.',
            data: null,
            forward_url: route('swift-auth.login.form'),
        );
    }
}
