<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MonevProgresSpseSeeder extends Seeder
{
    public function run()
    {
        $dataFile = APPPATH . 'Database/MonevProgresData.php';
        if (!file_exists($dataFile)) {
            echo "File MonevProgresData.php tidak ditemukan.\n";
            return;
        }

        $items = require $dataFile;
        if (!is_array($items) || empty($items)) {
            echo "Data di MonevProgresData.php kosong.\n";
            return;
        }

        $db = \Config\Database::connect();
        $builder = $db->table('monev_progres_spse');

        $now = date('Y-m-d H:i:s');
        $currentYear = (int) date('Y');

        foreach ($items as $row) {
            $tahun = (int) ($row['tahun'] ?? $currentYear);
            $no    = (int) ($row['no'] ?? 0);

            $dataInsert = array_merge($row, [
                'tahun'       => $tahun,
                'update_data' => $row['update_data'] ?? '29 September 2026',
                'update_rup'  => $row['update_rup'] ?? '15 September 2026',
                'updated_at'  => $now,
            ]);

            // Cek apakah data sudah ada berdasarkan no dan tahun
            $existing = $builder->where('no', $no)->where('tahun', $tahun)->get()->getRowArray();
            if ($existing) {
                $builder->where('id', $existing['id'])->update($dataInsert);
            } else {
                $dataInsert['created_at'] = $now;
                $builder->insert($dataInsert);
            }
        }

        echo "Berhasil mengisi/memperbarui " . count($items) . " baris data monev_progres_spse.\n";
    }
}
