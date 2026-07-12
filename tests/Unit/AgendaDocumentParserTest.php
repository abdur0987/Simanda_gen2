<?php

use App\Services\AgendaDocumentParser;

it('parses agenda fields from an invitation letter text', function () {
    $text = <<<'TEXT'
Nomor : 1469/PWI-LPG/VII/2026
Lampiran : ---
Perihal : Permohonan Menjadi Narasumber UKW

Kepada Yth,
Kepala Kanwil Kemenag Provinsi Lampung
Bapak Dr. H. Zulkarnain, S.Ag., M.Hum
Di -
Tempat

Sehubungan dengan pelaksanaan Uji Kompetensi Wartawan (UKW) Angkatan XXXVIII PWI
Provinsi Lampung, yang akan dilaksanakan pada :

Hari/Tanggal : Kamis, 9 Juli 2026.
Pukul : 09.00 Wib - selesai.
Tempat : Balai Wartawan H. Solfian Akhmad/PWI Lampung, Lt III
Jl. Ahmad Yani No. 4/7 Bandar Lampung

Perlu kami sampaikan bahwa peserta UKW angkatan 38 PWI Lampung adalah wartawan.
TEXT;

    $agenda = app(AgendaDocumentParser::class)->parseText($text);

    expect($agenda['nama_agenda'])->toBe('Permohonan Menjadi Narasumber UKW')
        ->and($agenda['tanggal_agenda'])->toBe('2026-07-09')
        ->and($agenda['jam_mulai'])->toBe('09:00')
        ->and($agenda['jam_selesai'])->toBeNull()
        ->and($agenda['tempat_agenda'])->toBe('Balai Wartawan H. Solfian Akhmad/PWI Lampung, Lt III Jl. Ahmad Yani No. 4/7 Bandar Lampung')
        ->and($agenda['kehadiran'])->toBe([
            'Kepala Kanwil Kemenag Provinsi Lampung',
            'Bapak Dr. H. Zulkarnain, S.Ag., M.Hum',
        ]);
});
