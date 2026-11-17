@extends('layouts.app')

@section('title', 'Editar cliente')
@section('heading', 'Editar cliente')
@section('subheading', $client->nombre)

@section('content')
    <form method="POST" action="{{ route('clients.update', $client) }}">
        @csrf
        @method('PUT')
        @include('clients._form')
    </form>
@endsection
