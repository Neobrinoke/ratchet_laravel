<?php

namespace App\Http\Controllers;

use App\Message;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $messages = Message::query()
                ->where(function (Builder $builder) use ($request) {
                    return $builder
                        ->where('sender_id', '=', $request->input('receiver_id'))
                        ->where('receiver_id', '=', auth()->id());
                })->orWhere(function (Builder $builder) use ($request) {
                    return $builder
                        ->where('sender_id', '=', auth()->id())
                        ->where('receiver_id', '=', $request->input('receiver_id'));
                })
                ->orderBy('sent_at');

            return response()->json($messages->get());
        }

        return null;
    }
}
