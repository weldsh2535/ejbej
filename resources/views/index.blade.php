@extends('layouts.app')

@section('title', 'Home')

@section('body')
    @include('components.navbar')
    @include('components.banner')
    @include('components.category-list')
    @include('components.product-list')
    @include('components.testimonials')
    @include('components.blog-list')
    @include('components.footer')
@endsection