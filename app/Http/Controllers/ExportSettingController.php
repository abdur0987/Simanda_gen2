<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ExportSetting;

class ExportSettingController extends Controller
{
    public function index()
    {
        return view('pages.export_setting.export_setting-index', [
            'type_menu' => 'Export Setting',
            'title' => 'Export Setting',
        ]);
    }

    public function store(Request $request)
    {
        $exportSetting = ExportSetting::first()->update([
            'ttd_nama_jabatan' => $request->ttd_nama_jabatan,
            'ttd_nama_lengkap' => $request->ttd_nama_lengkap,
        ]);

        if ($exportSetting) {
            return response()->json(['status' => true, 'message' => 'Berhasil']);
        } else {
            return response()->json(['status' => false, 'message' => 'Gagal']);
        }
    }

    public function show(Request $request)
    {
        $exportSetting = ExportSetting::first();
        return response()->json($exportSetting);
    }
}
