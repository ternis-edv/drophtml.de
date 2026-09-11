@extends('layouts.landing')

@section('content')
    @include('partials.landing.hero')
    @include('partials.landing.upload-demo')
    @include('partials.landing.interactive-demo')
    @include('partials.landing.media-cloud')
    @include('partials.landing.bento-grid')
    @include('partials.landing.stats')
    @include('partials.landing.testimonials')
    @include('partials.landing.pricing')
    @include('partials.landing.faq')
    @include('partials.landing.cta')
@endsection