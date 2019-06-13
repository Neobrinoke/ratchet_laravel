@extends('layouts.app')

@section('content')
    <chat :user-id="parseInt('{{ auth()->id() }}')"></chat>
@endsection
