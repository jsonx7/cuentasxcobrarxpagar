@extends('layouts.app')

@section('title', 'Nuevo proveedor')
@section('heading', 'Nuevo proveedor')
@section('subheading', 'Registra un proveedor para crear obligaciones por pagar.')

@section('content')
    <form method="POST" action="{{ route('suppliers.store') }}">
        @csrf
        @include('suppliers._form')
    </form>
@endsection
