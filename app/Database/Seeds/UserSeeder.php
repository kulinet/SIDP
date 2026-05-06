<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'username'     => 'admin',
                'password'     => 'admin123',
                'role'         => 'admin',
                'nama_lengkap' => 'Administrator SIDP',
                'kd_satker'    => null,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ],
            [
                'username'     => 'opd_sekretariat',
                'password'     => 'opd123',
                'role'         => 'opd',
                'nama_lengkap' => 'Sekretariat Daerah',
                'kd_satker'    => '98054', // Example satker from API
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]
        ];

        // Using individual inserts to trigger hashPassword in Model
        $model = new \App\Models\UserModel();
        foreach ($data as $user) {
            $model->insert($user);
        }
    }
}
