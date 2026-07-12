<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index()
    {
        return view('pages.role.role-index', [
            'type_menu' => 'Role',
            'title' => 'Role',
        ]);
    }

    public function store(Request $request)
    {
        $role = Role::create(['name' => $request->roleName]);

        if ($role) {
            return response()->json(['status' => true, 'message' => 'Berhasil']);
        } else {
            return response()->json(['status' => false, 'message' => 'Gagal']);
        }
    }


    public function showAll(Request $request)
    {
        if (!empty($request->search)) {
            $roles = Role::with('permissions')->where('name', 'LIKE', '%' . $request->search . '%')->get();
        } else {
            $roles = Role::with('permissions')->get();
        }
        return response()->json($roles);
    }

    public function show(Request $request, $roleId)
    {
        $role = Role::where('id', $roleId)->first();
        return response()->json($role);
    }

    public function update(Request $request, $roleId)
    {
        // Find the role you want to rename
        $role = Role::findById($roleId);

        if ($role) {
            $role->name = $request->roleName;
            $role->save();

            return response()->json(['status' => true, 'message' => 'Berhasil Mengubah Role']);
        } else {
            return response()->json(['status' => false, 'message' => 'Role Tidak Ditemukan']);
        }
    }

    public function destroy(Request $request, $roleId)
    {
        // Find the role you want to rename
        $role = Role::findById($roleId);

        if ($role) {
            $role->delete();

            return response()->json(['status' => true, 'message' => 'Berhasil Menghapus Role']);
        } else {
            return response()->json(['status' => false, 'message' => 'Role Tidak Ditemukan']);
        }
    }

    public function assignPermission(Request $request, $roleId)
    {
        $role = Role::findById($roleId);
        $permission = Permission::findById($request->permission_id);

        if ($role && $permission) {
            $role->givePermissionTo($permission);
            return response()->json(['status' => true, 'message' => 'Permission berhasil ditambahkan ke role']);
        } else {
            return response()->json(['status' => false, 'message' => 'Role atau Permission tidak ditemukan']);
        }
    }

    public function removePermission(Request $request, $roleId)
    {
        $role = Role::findById($roleId);
        $permission = Permission::findById($request->permission_id);

        if ($role && $permission) {
            $role->revokePermissionTo($permission);
            return response()->json(['status' => true, 'message' => 'Permission berhasil dihapus dari role']);
        } else {
            return response()->json(['status' => false, 'message' => 'Role atau Permission tidak ditemukan']);
        }
    }
}
