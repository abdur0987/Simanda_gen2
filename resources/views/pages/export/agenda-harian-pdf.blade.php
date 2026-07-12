<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Agenda Harian</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 4px 8px;
        }

        th {
            background: #fff;
        }

        table.no-border,
        table.no-border td {
            border: none;
        }

        table.agenda-content th,
        table.agenda-content td {
            padding-top: 20px;
            padding-bottom: 20px;
            line-height: 1.5;
        }
    </style>
</head>

<body>
    <table class="no-border">
        <td style="text-align: center;">
            <img src="{{'data:image/png;base64,' . base64_encode(file_get_contents(public_path('img/kemenag-logo.png')))}}"
                style="width: 100px; height: auto;">
            <br>
            <br>
            <br>
            <div style="font-size: 14px">
                <span style="font-weight: bold;">AGENDA HARIAN</span>
                <br>
                Kepala Kanwil Kementerian Agama
                <br>
                Provinsi Lampung
            </div>
        </td>
    </table>
    <table class="no-border" style="font-weight: bold; font-size: 13px; margin-top: 20px;">
        <tr>
            <td style="width: 10%;">Hari</td>
            <td>: {{ $tanggal ? \Carbon\Carbon::parse($tanggal)->translatedFormat('l') : '' }}</td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td>: {{ $tanggal ? \Carbon\Carbon::parse($tanggal)->format('d/m/Y') : '' }}</td>
        </tr>
    </table>
    <table class="agenda-content">
        <thead>
            <tr>
                <th>No.</th>
                <th>Jam</th>
                <th>Kegiatan</th>
                <th>Tempat</th>
                <th>Pakaian</th>
                <th>Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            @forelse($agenda as $i => $item)
                <tr>
                    <td style="text-align: center;">{{ $i + 1 }}</td>
                    <td style="text-align: center;">
                        {{ \Carbon\Carbon::parse($item->jam_mulai)->format('H.i') }} WIB
                        s/d
                        {{ $item->jam_selesai ? \Carbon\Carbon::parse($item->jam_selesai)->format('H.i') : 'Selesai' }}
                    </td>
                    <td style="text-align: center;">{{ $item->nama_agenda }}</td>
                    <td style="text-align: center;">{{ $item->tempat_agenda }}</td>
                    <td style="text-align: center;">{{ $item->pakaian }}</td>
                    <td style="text-align: center;">{{ $item->kehadiran_text }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" align="center">Tidak ada agenda</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <br>
    <span style="font-style: italic;"><span style="font-weight: bold;">Note:</span> Apabila ada tambahan dan atau perubahan Mohon info agar segera diperbaharui.</span>
    <table class="no-border" style="margin-top: 5em">
        <tr>
            <td style="width: 65%;"></td>
            <td>
                Bandar Lampung, &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ \Carbon\Carbon::parse($tanggal)->translatedFormat('F') }} {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('Y') }}
                <br>
                {{ @$exportSetting->ttd_nama_jabatan }}
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                {{ @$exportSetting->ttd_nama_lengkap }}
            </td>
        </tr>
    </table>
</body>

</html>