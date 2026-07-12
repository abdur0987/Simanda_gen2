<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function store(Request $request)
    {
        $permission = Permission::create(['name' => $request->permissionName]);

        if ($permission) {
            return response()->json(['status' => true, 'message' => 'Berhasil']);
        } else {
            return response()->json(['status' => false, 'message' => 'Gagal']);
        }
    }


    public function showAll(Request $request)
    {
        if (!empty($request->search)) {
            $permissions = Permission::where('name', 'LIKE', '%' . $request->search . '%')->get();
        } else {
            $permissions = Permission::all();
        }
        return response()->json($permissions);
    }

    public function show(Request $request, $permissionId)
    {
        $permission = Permission::where('id', $permissionId)->first();
        return response()->json($permission);
    }

    public function update(Request $request, $permissionId)
    {
        // Find the permission you want to rename
        $permission = Permission::findById($permissionId);

        if ($permission) {
            $permission->name = $request->permissionName;
            $permission->save();

            return response()->json(['status' => true, 'message' => 'Berhasil Mengubah Permission']);
        } else {
            return response()->json(['status' => false, 'message' => 'Permission Tidak Ditemukan']);
        }
    }

    public function destroy(Request $request, $permissionId)
    {
        // Find the permission you want to rename
        $permission = Permission::findById($permissionId);

        if ($permission) {
            // Cek apakah permission masih terhubung ke role
            if ($permission->roles()->count() > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Permission tidak bisa dihapus karena masih digunakan oleh role.'
                ]);
            } else {
                $permission->delete();

                return response()->json(['status' => true, 'message' => 'Berhasil Menghapus Permission']);
            }
        } else {
            return response()->json(['status' => false, 'message' => 'Permission Tidak Ditemukan']);
        }
    }
}
