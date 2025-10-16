<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        collect([
            [
                'name' => 'Demo Super Admin',
                'email' => 'admin.demo@example.com',
                'role' => 'Super',
            ],
            [
                'name' => 'Demo Staff',
                'email' => 'staff.demo@example.com',
                'role' => 'Admin',
            ],
            [
                'name' => 'Demo Guest',
                'email' => 'guest.demo@example.com',
                'role' => 'Customer',
            ],
        ])->each(function (array $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'password' => Hash::make('password'),
                    'random_key' => Str::random(60),
                ]
            );
        });
    }
}
