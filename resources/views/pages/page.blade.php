@extends('layouts.storefront')

@section('title', $page->meta_title ?: $page->title)
@section('description', $page->meta_description ?: (\Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $page->content))), 160, '…') ?: config('storefront.description')))

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
