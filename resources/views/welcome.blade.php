@extends('layouts.landing')

@section('content')
    @include('partials.landing.hero')
    @include('partials.landing.upload-demo')
    @include('partials.landing.interactive-demo')
    @include('partials.landing.stats')
    @include('partials.landing.bento-grid')
    @include('partials.landing.pricing')
@endsection