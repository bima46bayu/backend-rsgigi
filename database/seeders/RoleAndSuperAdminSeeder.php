<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Location;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RoleAndSuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Buat role
        $role = Role::firstOrCreate(['name' => 'super-admin']);

        // Buat default cabang
        $location = Location::firstOrCreate([
            'name' => 'Cabang Pusat'
        ], [
            'address' => 'Jl. Utama No. 1'
        ]);

        // Buat super admin
        $user = User::firstOrCreate(
            ['email' => 'adminpusat@gmail.com'],
            [
                'name' => 'God',
                'password' => Hash::make('admin123'),
                'location_id' => $location->id
            ]
        );

        $user->assignRole($role);
    }
}
