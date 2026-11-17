@extends('layouts.app')

@section('title', 'Editar proveedor')
@section('heading', 'Editar proveedor')
@section('subheading', $supplier->nombre)

@section('content')
    <form method="POST" action="{{ route('suppliers.update', $supplier) }}">
        @csrf
        @method('PUT')
        @include('suppliers._form')
    </form>
@endsection
