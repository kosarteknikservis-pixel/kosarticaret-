@extends('layouts.shop')
@section('title', 'Sayfa Bulunamadı')

@section('content')
    @include('errors.partials.missing-page', ['code' => '404'])
@endsection
