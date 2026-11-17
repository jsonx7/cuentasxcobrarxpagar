@extends('layouts.app')

@section('title', 'Nuevo material')
@section('heading', 'Nuevo material')
@section('subheading', 'Registra materiales que pueden usarse como parte de un abono.')

@section('content')
    <form method="POST" action="{{ route('materials.store') }}">
        @csrf
        @include('materials._form')
    </form>
@endsection
