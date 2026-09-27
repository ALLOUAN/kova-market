@extends('layouts.storefront')

@section('title', $page->meta_title ?: $page->title)
@section('description', $page->meta_description ?: config('storefront.description'))

@section('content')
    <x-page-header :title="$page->title" />

    <div class="rbt-section-gap2">
        <div class="container">
            <div class="row justify-content-center">
                {{-- Content comes from the back-office rich editor; it is sanitized before display. --}}
                <article class="col-lg-9 rbt-page-content">
                    {!! str($page->content)->sanitizeHtml() !!}
                </article>
            </div>
        </div>
    </div>
@endsection
