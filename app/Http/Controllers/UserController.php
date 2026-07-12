<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        return view('pages.users.users-index', [
            'type_menu' => 'User',
            'title' => 'User',
        ]);
    }

    public function store(Request $request)
    {
        $user = User::create([
            'name' => $request->userName,
            'username' => $request->userUserName,
            'email' => $request->userEmail,
            'password' => bcrypt($request->userPassword),
        ]);

        if ($user && $request->role) {
            $user->assignRole($request->role);
            return response()->json(['status' => true, 'message' => 'Berhasil']);
        } else {
            return response()->json(['status' => false, 'message' => 'Gagal']);
        }
    }

    public function showAll(Request $request)
    {
        if (!empty($request->search)) {
            $users = User::with('roles')->where('name', 'LIKE', '%' . $request->search . '%')->get();
        } else {
            $users = User::with('roles')->get();
        }
        return response()->json($users);
    }

    public function show(Request $request, $userId)
    {
        $user = User::with('roles')->find($userId);
        return response()->json($user);
    }

    public function update(Request $request, $userId)
    {
        $user = User::find($userId);

        if ($user) {
            $user->name = $request->userName;
            $user->username = $request->userUserName;
            $user->email = $request->userEmail;
            $user->is_active = $request->is_active ? true : false;
            if ($request->userPassword) {
                $user->password = bcrypt($request->userPassword);
            }
            $user->save();

            if ($request->role) {
                $user->syncRoles([$request->role]);
            }

            return response()->json(['status' => true, 'message' => 'Berhasil Mengubah User']);
        } else {
            return response()->json(['status' => false, 'message' => 'User Tidak Ditemukan']);
        }
    }

    public function destroy(Request $request, $userId)
    {
        $user = User::find($userId);

        if ($user) {
            $user->delete();

            return response()->json(['status' => true, 'message' => 'Berhasil Menghapus User']);
        } else {
            return response()->json(['status' => false, 'message' => 'User Tidak Ditemukan']);
        }
    }
}