@extends('layouts.app')

@section('title', 'Nuevo usuario')
@section('heading', 'Nuevo usuario')
@section('subheading', 'Crea una cuenta de acceso al sistema.')

@section('content')
    <form method="POST" action="{{ route('users.store') }}">
        @csrf
        @include('users._form')
    </form>
@endsection
