@extends('layouts.app')

@section('title', 'Editar usuario')
@section('heading', 'Editar usuario')
@section('subheading', $user->name)

@section('content')
    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')
        @include('users._form')
    </form>
@endsection
