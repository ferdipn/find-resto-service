<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use \App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    private $users = [
        [
            'name' => 'admin',
            'email' => 'admin@mail.com',
            'type' => 'administrator',
            'password' => 'pasport'
        ],
        [
            'name' => 'Adek',
            'email' => 'adek@mail.com',
            'type' => 'customer',
            'password' => 'pasport'
        ],
        [
            'name' => 'Diki',
            'email' => 'diki@mail.com',
            'type' => 'customer',
            'password' => 'pasport'
        ],
    ];

    public function run(): void
    {
        foreach ($this->users as $user) {

            $user['password'] = Hash::make($user['password']);

            $user = User::create($user);
        }
    }
}
