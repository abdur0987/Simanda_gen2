<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jabatan extends Model
{
    use HasFactory;

    protected $table = 'jabatan';

    protected $fillable = [
        'nama_jabatan',
        'deskripsi',
        'order',
        'is_show'
    ];

    public function agendas()
    {
        return $this->belongsToMany(Agenda::class, 'agenda_jabatan');
    }
}
