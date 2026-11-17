@extends('layouts.app')

@section('title', 'Nuevo cliente')
@section('heading', 'Nuevo cliente')
@section('subheading', 'Registra un cliente externo para crear contratos por cobrar.')

@section('content')
    <form method="POST" action="{{ route('clients.store') }}">
        @csrf
        @include('clients._form')
    </form>
@endsection
