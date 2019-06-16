@extends('layouts.app')

@section('content')
    <conversation
        :user-id="parseInt('{{ auth()->id() }}')"
        user-data="{{ \App\User::all()->toJson() }}"
    ></conversation>
@endsection
