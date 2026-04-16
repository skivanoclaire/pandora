<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'nip' => '000000000000000001',
                'name' => 'Administrator PANDORA',
                'email' => 'admin@pandora.kaltaraprov.go.id',
                'password' => Hash::make('pandora2026!'),
                'role' => 'admin',
            ],
            [
                'nip' => '000000000000000002',
                'name' => 'Demo HR',
                'email' => 'hr@pandora.kaltaraprov.go.id',
                'password' => Hash::make('pandora2026!'),
                'role' => 'hr',
            ],
            [
                'nip' => '000000000000000003',
                'name' => 'Demo Pimpinan',
                'email' => 'pimpinan@pandora.kaltaraprov.go.id',
                'password' => Hash::make('pandora2026!'),
                'role' => 'pimpinan',
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['nip' => $data['nip']],
                $data
            );
        }
    }
}
