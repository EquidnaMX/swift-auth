<?php

/**
 * Provides CRUD endpoints for SwiftAuth user management.
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
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Equidna\Toolkit\Exceptions\BadRequestException;
use Equidna\Toolkit\Exceptions\ForbiddenException;
use Equidna\Toolkit\Helpers\ResponseHelper;
use Inertia\Response;
use Equidna\SwifthAuth\Facades\SwiftAuth;
use Equidna\SwifthAuth\Models\Role;
use Equidna\SwifthAuth\Models\User;
use Equidna\SwifthAuth\Traits\SelectiveRender;

/**
 * Manages SwiftAuth user lifecycle actions (listing, creation, updates, and deletion).
 *
 * Renders blade or Inertia resources and emits toolkit responses that honor the current context.
 */
class UserController extends Controller
{
    use SelectiveRender;

    /**
     * Displays the paginated user list optionally filtered by search term.
     *
     * @param  Request       $request  HTTP request containing the optional search filter.
     * @return View|Response           Blade or Inertia response with pagination data.
     */
    public function index(Request $request): View|Response
    {
        $users = User::search($request->get('search'))
            ->paginate(10);

        return $this->render(
            'swift-auth::user.index',
            'user/Index',
            [
                'users' => $users,
                'actions' => Config::get('swift-auth.actions'),
            ],
        );
    }

    /**
     * Shows the registration form for creating a new user.
     *
     * @param  Request       $request  HTTP request context.
     * @return View|Response           Blade or Inertia response with role list.
     */
    public function register(Request $request): View|Response
    {
        $roles = Role::orderBy('name')->get();

        return $this->render(
            'swift-auth::user.register',
            'user/Register',
            [
                'roles' => $roles,
            ],
        );
    }

    /**
     * Stores a new user and assigns the selected role.
     *
     * @param  Request                   $request  HTTP request with registration payload.
     * @return RedirectResponse|JsonResponse       Context-aware created response.
     * @throws BadRequestException                 When validation fails.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:Users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|exists:Roles,id_role',
        ]);

        if ($validator->fails()) {
            throw new BadRequestException(
                'Registration data invalid.',
                errors: $validator->errors()->toArray()
            );
        }

        $payload = $validator->validated();

        $user = User::create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => Hash::make($payload['password']),
        ]);

        $user->roles()->attach($payload['role']);

        if (SwiftAuth::check()) {
            return ResponseHelper::created(
                message: 'Registration successful.',
                data: [
                    'user_id' => $user->getKey(),
                ],
                forward_url: route('swift-auth.users.index'),
            );
        }

        SwiftAuth::login($user);

        return ResponseHelper::created(
            message: 'Registration successful.',
            data: [
                'user_id' => $user->getKey(),
            ],
            forward_url: route('swift-auth.users.index'),
        );
    }

    /**
     * Displays the detail page for a specific user.
     *
     * @param  Request       $request  HTTP request context.
     * @param  string        $id_user  Identifier of the user to show.
     * @return View|Response           Blade or Inertia response with user data.
     */
    public function show(Request $request, string $id_user): View|Response
    {
        $user = User::findOrFail($id_user);
        $roles = Role::orderBy('name')->get();

        return $this->render(
            'swift-auth::user.show',
            'user/Details',
            [
                'user' => $user,
                'roles' => $roles,
            ],
        );
    }

    /**
     * Updates the selected user name and roles.
     *
     * @param  Request                   $request  HTTP request containing changes.
     * @param  string                    $id_user  Identifier of the user to update.
     * @return RedirectResponse|JsonResponse       Context-aware success response.
     * @throws BadRequestException                 When provided data is invalid.
     */
    public function update(Request $request, string $id_user): RedirectResponse|JsonResponse
    {
        $user = User::findOrFail($id_user);

        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'sometimes|string|max:255',
                'roles' => 'sometimes|array',
                'roles.*' => 'integer|exists:Roles,id_role',
                'role' => 'sometimes|integer|exists:Roles,id_role',
            ]
        );

        if ($validator->fails()) {
            throw new BadRequestException(
                'User update failed.',
                errors: $validator->errors()->toArray()
            );
        }

        $payload = $validator->validated();

        $user->update([
            'name' => $payload['name'] ?? $user->name,
        ]);

        $roleIds = [];

        if (isset($payload['roles'])) {
            $roleIds = $payload['roles'];
        } elseif (isset($payload['role'])) {
            $roleIds = [$payload['role']];
        }

        if (!empty($roleIds)) {
            $user->roles()->sync($roleIds);
        }

        return ResponseHelper::success(
            message: 'User updated successfully.',
            data: [
                'user_id' => $user->getKey(),
            ],
            forward_url: route('swift-auth.users.index'),
        );
    }

    /**
     * Deletes a user from the system.
     *
     * @param  Request                   $request  HTTP request context.
     * @param  string                    $id_user  Identifier for the user to delete.
     * @return RedirectResponse|JsonResponse       Context-aware success response.
     * @throws ForbiddenException                  When attempting to delete your own account.
     */
    public function destroy(Request $request, string $id_user): RedirectResponse|JsonResponse
    {
        if ((int) SwiftAuth::id() === (int) $id_user) {
            throw new ForbiddenException('You cannot delete your own account.');
        }

        $user = User::findOrFail($id_user);
        $user->delete();

        return ResponseHelper::success(
            message: 'User successfully deleted.',
            data: [
                'user_id' => (int) $id_user,
            ],
            forward_url: route('swift-auth.users.index'),
        );
    }
}
