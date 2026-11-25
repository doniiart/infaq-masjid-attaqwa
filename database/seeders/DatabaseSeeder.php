<?php

namespace Database\Seeders;

use App\Models\MosqueSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@masjidattaqwa.id'],
            [
                'name' => 'Super Admin Masjid At-Taqwa',
                'password' => Hash::make('Password123!'),
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@masjidattaqwa.id'],
            [
                'name' => 'Pengurus Takmir Masjid At-Taqwa',
                'password' => Hash::make('Password123!'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        MosqueSetting::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Masjid At-Taqwa',
                'address' => 'Candi Rejo, RT 23 RW 09, Jelobo, Wonosari, Klaten, Jawa Tengah 57473',
                'email' => 'masjidattaqwa2309@gmail.com',
                'phone' => null,
                'bank_accounts' => null,
                'about_description' => 'Pusat informasi pengelolaan infaq dan transparansi pembangunan Masjid At-Taqwa Candi Rejo, Jelobo, Wonosari, Klaten.',
                'logo_path' => '/images/logo-masjid-attaqwa.png',
                'updated_by' => $superAdmin->id,
            ]
        );
    }
}