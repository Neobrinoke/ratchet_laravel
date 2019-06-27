<?php

namespace App\Http\Controllers;

use App\Chat;

class ChatController extends Controller
{
    public function messages(Chat $chat)
    {
        sleep(1);
        return $chat->messages()->orderByDesc('id')->paginate(50);
    }
}
