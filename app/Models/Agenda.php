<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agenda extends Model
{
    use HasFactory;

    protected $table = 'agenda';

    protected $casts = [
        'kehadiran' => 'array',
    ];

    protected $appends = ['kehadiran_text'];

    protected $fillable = [
        'nama_agenda',
        'tanggal_agenda',
        'jam_mulai',
        'jam_selesai',
        'tempat_agenda',
        'sifat_agenda',
        'pakaian',
        'kehadiran',
        'is_done',
    ];

    // Relasi ke Jabatan (many-to-many)
    public function jabatans()
    {
        return $this->belongsToMany(Jabatan::class, 'agenda_jabatan');
    }

    public function links()
    {
        return $this->hasMany(AgendaLink::class);
    }

    // Fungsi menampilkan teks kehadiran dalam bahasa formal
    public function getKehadiranTextAttribute()
    {
        $kehadiranText = "";

        if (count($this->jabatans) > 0) {
            $kehadiranText .= "Hadir ";
            // Jika Kakanwil berhalangan hadir, maka diwakili
            if (!$this->jabatans->contains('order', 1)) {
                $kehadiranText .= " diwakili ";
            }

            $isBersama = false;

            // Jika ada lebih dari satu jabatan dan salah satunya adalah jabatan dengan order 1 (Kakanwil), maka tampilkan "bersama" di antara jabatan tersebut.
            if ($this->jabatans->contains('order', 1) && $this->jabatans->count() > 1) {
                $isBersama = true;
            }

            $iteration = 1;
            $iterationCount = $this->jabatans->count();

            foreach ($this->jabatans as $jabatan) {
                $kehadiranText .= $jabatan->nama_jabatan;

                // Jika ini adalah jabatan dengan order 1 (Kakanwil) dan ada lebih dari 1 jabatan, maka tambahkan "bersama" setelah nama jabatan tersebut.
                if ($isBersama && $jabatan->order == 1) {
                    $kehadiranText .= " bersama ";
                }

                // Jika ini adalah jabatan terakhir, dan ada lebih dari 1 jabatan, serta tidak ada jabatan dengan order 1, maka tambahkan "dan" sebelum jabatan terakhir.
                if (($iteration == $this->jabatans->count() - 1) && $this->jabatans->count() > 1) {
                    $kehadiranText .= " dan ";
                // Jika ini bukan jabatan terakhir, dan ada lebih dari 2 jabatan, serta tidak ada jabatan dengan order 1, maka tambahkan koma setelah nama jabatan.
                } elseif ((!$iterationCount && $jabatan->order != 1) && $iteration != $this->jabatans->count() - 1) {
                    $kehadiranText .= ", ";
                }

                $iteration++;
            }
        } else {
            $kehadiranText .= "";
        }


        return $kehadiranText;
    }
}