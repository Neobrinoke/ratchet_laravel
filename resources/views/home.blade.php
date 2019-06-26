@extends('layouts.app')

@section('content')
    <conversation
        current-user-data="{{ auth()->user()->toJson() }}"
        user-data="{{ \App\User::all()->toJson() }}"
        chat-data="{{ auth()->user()->chats->toJson() }}"
    ></conversation>
@endsection
