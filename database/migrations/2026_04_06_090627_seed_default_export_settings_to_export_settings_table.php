<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('export_settings')->insert([
            ['ttd_nama_jabatan' => 'Ketua Tim Humas', 'ttd_nama_lengkap' => 'Solihin', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('export_settings')->truncate();
    }
};
