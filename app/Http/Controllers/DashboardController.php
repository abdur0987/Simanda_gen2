<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Agenda;
use App\Models\ExportSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Jabatan;
use App\Services\AgendaDocumentParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DashboardController extends Controller
{
    /**
     * Show main dashboard.
     */
    public function index(): View
    {
        return view('pages.dashboard', [
            'type_menu' => 'Dashboard',
            'title' => 'Dashboard',
        ]);
    }

    public function showAll(Request $request)
    {
        $query = Agenda::with([
            'jabatans' => function ($q) {
                $q->orderBy('order', 'ASC');
            },
            'links'
        ]); 

        if (!empty($request->search)) {
            $query->where('nama_agenda', 'LIKE', '%' . $request->search . '%');
            $query->orWhere('kehadiran', 'LIKE', '%' . $request->search . '%');
        }
        if (!empty($request->tanggal)) {
            $query->whereDate('tanggal_agenda', $request->tanggal);
        }

        if (!empty($request->jabatan_ids)) {
            $jabatanIds = is_array($request->jabatan_ids) ? $request->jabatan_ids : explode(',', $request->jabatan_ids);
            $query->whereHas('jabatans', function ($q) use ($jabatanIds) {
                $q->whereIn('jabatan_id', $jabatanIds);
            });
        }

        if (!empty($request->sifat_agenda)) {
            $query->where('sifat_agenda', $request->sifat_agenda);
        }

        if (!empty($request->is_paginate)) {
            $agenda = $query->orderByRaw('tanggal_agenda DESC, jam_mulai ASC')->paginate(10);
        } else {
            $agenda = $query->orderByRaw('tanggal_agenda DESC, jam_mulai ASC')->get();
        }
        return response()->json($agenda);

    }

    public function store(Request $request)
    {
        $kehadiran = $request->kehadiran && count($request->kehadiran) > 0 ? json_encode($request->kehadiran) : json_encode([""]);

        $agenda = Agenda::create([
            'nama_agenda' => $request->nama_agenda,
            'tanggal_agenda' => $request->tanggal_agenda,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'tempat_agenda' => $request->tempat_agenda,
            'pakaian' => $request->pakaian,
            'sifat_agenda' => $request->sifat_agenda,
            // 'kehadiran' => $request->kehadiran ? json_encode($request->kehadiran) : '',
            'kehadiran' => $kehadiran,
            'is_done' => $request->is_done,
        ]);
        if ($agenda && $request->jabatan_ids) {
            $agenda->jabatans()->sync($request->jabatan_ids);
        }

        // Simpan links
        if ($agenda && $request->links) {
            foreach ($request->links as $url) {
                if ($url) {
                    $agenda->links()->create(['url' => $url]);
                }
            }
        }

        return response()->json(['status' => true, 'message' => 'Berhasil']);
    }

    public function importDocument(Request $request, AgendaDocumentParser $parser): JsonResponse
    {
        if (!$request->user()->hasRole('Super Admin') && !$request->user()->hasRole('Protokol')) {
            abort(403);
        }

        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        try {
            $data = $parser->parse($request->file('document'));

            $agenda = DB::transaction(function () use ($data) {
                return Agenda::create([
                    'nama_agenda' => $data['nama_agenda'],
                    'tanggal_agenda' => $data['tanggal_agenda'],
                    'jam_mulai' => $data['jam_mulai'],
                    'jam_selesai' => $data['jam_selesai'],
                    'tempat_agenda' => $data['tempat_agenda'],
                    'pakaian' => $data['pakaian'],
                    'sifat_agenda' => $data['sifat_agenda'],
                    'kehadiran' => json_encode($data['kehadiran']),
                    'is_done' => $data['is_done'],
                ]);
            });

            return response()->json([
                'status' => true,
                'message' => 'Dokumen berhasil diproses menjadi agenda.',
                'agenda' => $agenda,
                'parsed' => $data,
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function show(Request $request, $id)
    {
        $agenda = Agenda::with('jabatans')->find($id);
        return response()->json($agenda);
    }

    public function update(Request $request, $id)
    {
        $agenda = Agenda::find($id);
        if ($agenda) {
            $agenda->nama_agenda = $request->nama_agenda;
            $agenda->tanggal_agenda = $request->tanggal_agenda;
            $agenda->jam_mulai = $request->jam_mulai;
            $agenda->jam_selesai = $request->jam_selesai;
            $agenda->tempat_agenda = $request->tempat_agenda;
            $agenda->pakaian = $request->pakaian;
            $agenda->sifat_agenda = $request->sifat_agenda;
            $agenda->is_done = $request->is_done;
            $agenda->kehadiran = $request->kehadiran ? json_encode($request->kehadiran) : '';
            $agenda->save();

            if ($request->jabatan_ids) {
                $agenda->jabatans()->sync($request->jabatan_ids);
            }

            // Update links
            $agenda->links()->delete();
            if ($request->links) {
                foreach ($request->links as $url) {
                    if ($url) {
                        $agenda->links()->create(['url' => $url]);
                    }
                }
            }

            return response()->json(['status' => true, 'message' => 'Berhasil Mengubah Agenda']);
        } else {
            return response()->json(['status' => false, 'message' => 'Agenda Tidak Ditemukan']);
        }
    }

    public function destroy(Request $request, $id)
    {
        $agenda = Agenda::find($id);
        if ($agenda) {
            $agenda->jabatans()->detach();
            $agenda->delete();
            return response()->json(['status' => true, 'message' => 'Berhasil Menghapus Agenda']);
        } else {
            return response()->json(['status' => false, 'message' => 'Agenda Tidak Ditemukan']);
        }
    }

    public function exportPdf(Request $request)
    {
        $tanggal = $request->tanggal;
        $query = Agenda::with([
            'jabatans' => function ($q) {
                $q->orderBy('order', 'ASC');
            }
        ]);
        if ($tanggal) {
            $query->whereDate('tanggal_agenda', $tanggal);
        }
        $agenda = $query->orderByRaw('tanggal_agenda DESC, jam_mulai ASC')->get();

        $exportSetting = ExportSetting::first();

        $pdf = Pdf::loadView('pages.export.agenda-harian-pdf', [
            'agenda' => $agenda,
            'tanggal' => $tanggal,
            'exportSetting' => $exportSetting,
        ])->setPaper('A4', 'potrait');
        // return $pdf->download('AGENDA-HARIAN-' . (date('d-m-Y', strtotime($tanggal)) ?? date('d-m-Y')) . '.pdf');
        return $pdf->stream('AGENDA-HARIAN-' . (date('d-m-Y', strtotime($tanggal)) ?? date('d-m-Y')) . '.pdf');
    }

    public function getAgendaLinks($id)
    {
        $agenda = Agenda::with('links')->find($id);
        if ($agenda) {
            return response()->json(['status' => true, 'agenda' => $agenda]);
        } else {
            return response()->json(['status' => false, 'message' => 'Agenda Tidak Ditemukan']);
        }
    }

    public function updateAgendaLinks(Request $request, $id)
    {
        $agenda = Agenda::find($id);
        if ($agenda) {
            // Hapus semua links yang ada
            $agenda->links()->delete();

            // Simpan links baru
            foreach ($request->links as $link) {
                if (!empty(trim($link['url'])) || !empty(trim($link['nama_link']))) {
                    $agenda->links()->create([
                        'nama_link' => $link['nama_link'] ?? '',
                        'url' => $link['url'] ?? '',
                    ]);
                }
            }

            return response()->json(['status' => true, 'message' => 'Berhasil Mengupdate Link Dokumentasi']);
        } else {
            return response()->json(['status' => false, 'message' => 'Agenda Tidak Ditemukan']);
        }
    }
}
