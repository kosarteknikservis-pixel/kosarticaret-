@extends('layouts.shop')



@section('title', 'Ana Sayfa')



@section('content')
    <div class="shop-page shop-page--home">
    @include('shop.partials.home-layout', ['homeRows' => $homeRows])



    @include('shop.partials.home-brands', ['brands' => $featuredBrands])
    </div>
@endsection

