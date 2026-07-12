<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/pegawai_kanwil.csv');

        if (!file_exists($path)) {
            $this->command->error("File tidak ditemukan: {$path}");
            return;
        }

        $file = fopen($path, 'r');

        // Header
        $header = fgetcsv($file);
        // $header = array_map('trim', $header);

        // FIX BOM
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

        // while (($row = fgetcsv($file, 0, ';')) !== false) {
        while (($row = fgetcsv($file)) !== false) {

            // if (count($header) !== count($row)) {
            //     $this->command->warn('Jumlah kolom header dan row tidak sama, dilewati');
            //     continue;
            // }

            // $row = array_map('trim', $row);
            $data = array_combine($header, $row);

            $dataValue = [
                'name' => $data['nama'] ?? null,
                'username' => $data['nip'] ?? null,
                'email' => $data['nip'].'@email.com' ?? null,
                'password' => Hash::make('12345'),
                'created_at' => now(),
            ];

            DB::table('users')->insertOrIgnore($dataValue);

            $this->command->info("Inserted: {$dataValue['email']} - {$dataValue['name']}");
            // $this->command->info(json_encode($data['nip']));
        }

        fclose($file);
    }
}