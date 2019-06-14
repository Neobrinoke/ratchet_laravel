@extends('layouts.app')

@section('content')
    @auth()
        <chat
            :user-id="parseInt('{{ auth()->id() }}')"
            user-data="{{ \App\User::all()->toJson() }}"
        ></chat>
    @endauth
@endsection
