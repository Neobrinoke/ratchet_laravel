<?php

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::query()->create([
            'name' => 'Neobrinoke',
            'email' => 'neobrinoke@gmail.com',
            'password' => Hash::make('neobrinoke'),
            'profile_picture_url' => 'https://randomuser.me/api/portraits/lego/0.jpg',
        ]);

        User::query()->create([
            'name' => 'Neobrinoke 2',
            'email' => 'neobrinoke2@gmail.com',
            'password' => Hash::make('neobrinoke'),
            'profile_picture_url' => 'https://randomuser.me/api/portraits/lego/6.jpg',
        ]);

        User::query()->create([
            'name' => 'Neobrinoke 3',
            'email' => 'neobrinoke3@gmail.com',
            'password' => Hash::make('neobrinoke'),
            'profile_picture_url' => 'https://randomuser.me/api/portraits/lego/8.jpg',
        ]);
    }
}
