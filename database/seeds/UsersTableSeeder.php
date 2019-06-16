<?php

use App\User;
use Illuminate\Database\Seeder;

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
            'password' => 'neobrinoke',
        ]);

        User::query()->create([
            'name' => 'Neobrinoke 2',
            'email' => 'neobrinoke2@gmail.com',
            'password' => 'neobrinoke',
        ]);
    }
}
