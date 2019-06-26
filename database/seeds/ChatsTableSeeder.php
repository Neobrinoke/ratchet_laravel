<?php

use App\Chat;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ChatsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /** @var Collection|User[] $users */
        $users = User::all();

        /** @var Collection|Chat[] $chats */
        $chats = collect();

        $chats->push(Chat::query()->create([
            'name' => 'Chat 1',
            'admin_id' => 1,
        ]));

        $chats->push(Chat::query()->create([
            'name' => 'Chat 2',
            'admin_id' => 1,
        ]));

        $chats->push(Chat::query()->create([
            'name' => 'Chat 3',
            'admin_id' => 1,
        ]));

        foreach ($chats as $chat) {
            $chat->members()->sync($users->pluck('id'));
        }
    }
}
