<?php

namespace Teleurban\SwiftAuth\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Teleurban\SwiftAuth\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Teleurban\SwiftAuth\Traits\SelectiveRender;

class RoleController extends Controller
{
    use SelectiveRender;

    public function index(Request $request)
    {
        $roles = Role::search($request->get("search"))->paginate(10);

        return $this->render('swift-auth::user.role.index', 'role/Index', ['roles' => $roles]);
    }

    public function create(Request $request)
    {
        return $this->render('swift-auth::user.role.create', 'role/Create');
    }

    public function store(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|unique:roles,name',
                'description' => 'required|string'
            ]
        );

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        Role::create(
            $request->name,
            $request->description,
            implode(',', $request->actions)
        );

        return redirect()
            ->route('swift-auth.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Request $request, string $id_role)
    {
        $role = Role::findOrFail($id_role);

        return view('swift-auth::user.role.edit')->with('role', $role);
    }

    public function update(Request $request, string $id_role)
    {
        $role = Role::findOrFail($id_role);

        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|unique:roles,name,' . $id_role,
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

    public function destroy(Request $request, string $id_role)
    {
        $role = Role::findOrFail($id_role);
        $role->delete();

        return redirect()
            ->route('swift-auth.roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
