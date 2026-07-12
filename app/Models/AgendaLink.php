<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgendaLink extends Model
{
    protected $fillable = ['agenda_id', 'nama_link', 'url'];

    public function agenda()
    {
        return $this->belongsTo(Agenda::class);
    }
}
