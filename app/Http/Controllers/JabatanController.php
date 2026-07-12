<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Jabatan;

class JabatanController extends Controller
{
    public function index()
    {
        return view('pages.jabatan.jabatan-index', [
            'type_menu' => 'Jabatan',
            'title' => 'Jabatan',
        ]);
    }

    public function store(Request $request)
    {
        $jabatan = Jabatan::create([
            'nama_jabatan' => $request->nama_jabatan,
            'deskripsi' => $request->deskripsi,
        ]);

        if ($jabatan) {
            return response()->json(['status' => true, 'message' => 'Berhasil']);
        } else {
            return response()->json(['status' => false, 'message' => 'Gagal']);
        }
    }

    public function showAll(Request $request)
    {
        if (!empty($request->search)) {
            $jabatan = Jabatan::where('nama_jabatan', 'LIKE', '%' . $request->search . '%')->orderBy('order')->get();
        } else {
            $jabatan = Jabatan::orderBy('order')->get();
        }
        return response()->json($jabatan);
    }

    public function getAll(Request $request)
    {
        if ($request->is_show_only == 1) {
            $jabatan = Jabatan::select('id', 'nama_jabatan')->where('is_show', true)->orderBy('order')->get();
        } else {
            $jabatan = Jabatan::select('id', 'nama_jabatan')->orderBy('order')->get();
        }
        return response()->json($jabatan);
    }

    public function show(Request $request, $id)
    {
        $jabatan = Jabatan::find($id);
        return response()->json($jabatan);
    }

    public function update(Request $request, $id)
    {
        $jabatan = Jabatan::find($id);

        if ($jabatan) {
            $jabatan->nama_jabatan = $request->nama_jabatan;
            $jabatan->deskripsi = $request->deskripsi;
            $jabatan->is_show = $request->is_show ? true : false;
            $jabatan->save();

            return response()->json(['status' => true, 'message' => 'Berhasil Mengubah Jabatan']);
        } else {
            return response()->json(['status' => false, 'message' => 'Jabatan Tidak Ditemukan']);
        }
    }

    public function destroy(Request $request, $id)
    {
        $jabatan = Jabatan::find($id);

        if ($jabatan) {
            $jabatan->delete();

            return response()->json(['status' => true, 'message' => 'Berhasil Menghapus Jabatan']);
        } else {
            return response()->json(['status' => false, 'message' => 'Jabatan Tidak Ditemukan']);
        }
    }

    public function updateOrder(Request $request)
    {
        $ids = $request->ids;
        foreach ($ids as $index => $id) {
            Jabatan::where('id', $id)->update(['order' => $index + 1]);
        }
        return response()->json(['status' => true]);
    }
}