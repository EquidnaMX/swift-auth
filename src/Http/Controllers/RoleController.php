<?php

namespace Teleurban\SwiftAuth\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Teleurban\SwiftAuth\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Teleurban\SwiftAuth\Traits\SelectiveRender;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class RoleController extends Controller
{
    use SelectiveRender;

    /**
     * Display a listing of the roles.
     *
     * @param  Request  $request
     * @return View|Response
     */
    public function index(Request $request): View|Response
    {
        $roles = Role::search($request->get("search"))->paginate(10);

        return $this->render('swift-auth::user.role.index', 'role/Index', ['roles' => $roles]);
    }

    /**
     * Show the form to create a new role.
     *
     * @param  Request  $request
     * @return View|Response
     */
    public function create(Request $request): View|Response
    {
        return $this->render('swift-auth::user.role.create', 'role/Create', [
            'actions' => Config::get('swift-auth.actions'),
        ]);
    }

    /**
     * Store a newly created role in storage.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|unique:Roles,name',
                'description' => 'required|string'
            ]
        );

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        Role::create([
            'name' => $request->name,
            'description' => $request->description,
            'actions' => implode(',', $request->actions)
        ]);

        return redirect()
            ->route('swift-auth.roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Show the form to edit the specified role.
     *
     * @param  Request  $request
     * @param  string   $id_role
     * @return View|Response
     */
    public function edit(Request $request, string $id_role): View|Response
    {
        $role = Role::findOrFail($id_role);

        return view('swift-auth::user.role.edit')->with('role', $role);
    }

    /**
     * Update the specified role in storage.
     *
     * @param  Request  $request
     * @param  string   $id_role
     * @return RedirectResponse
     */
    public function update(Request $request, string $id_role): RedirectResponse
    {
        $role = Role::findOrFail($id_role);

        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|unique:Roles,name,' . $id_role,
                'description' => 'required|string'
            ]
        );

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $role->update(
            [
                $request->name,
                $request->description,
                implode(',', $request->actions)
            ]
        );

        return redirect()
            ->route('swift-auth.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role from storage.
     *
     * @param  Request  $request
     * @param  string   $id_role
     * @return RedirectResponse
     */
    public function destroy(Request $request, string $id_role): RedirectResponse
    {
        $role = Role::findOrFail($id_role);

        $role->delete();

        return redirect()
            ->route('swift-auth.roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
