@extends('layouts.app')

@section('title', 'Editar material')
@section('heading', 'Editar material')
@section('subheading', $material->nombre)

@section('content')
    <form method="POST" action="{{ route('materials.update', $material) }}">
        @csrf
        @method('PUT')
        @include('materials._form')
    </form>
@endsection
