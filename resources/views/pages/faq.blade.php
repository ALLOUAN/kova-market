@extends('layouts.storefront')

@section('title', 'Questions fréquentes')

@section('content')
    <x-page-header title="Questions fréquentes" />

    <div class="rbt-section-gap2">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    @forelse ($topics as $topic => $faqs)
                        <h2 class="h5 mt--32 mb--16">{{ $topic }}</h2>
                        <div class="accordion rbt-accordion-style" id="faq-{{ $loop->index }}">
                            @foreach ($faqs as $faq)
                                <div class="accordion-item">
                                    <h3 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-answer-{{ $faq->id }}" aria-expanded="false" aria-controls="faq-answer-{{ $faq->id }}">
                                            {{ $faq->question }}
                                        </button>
                                    </h3>
                                    <div id="faq-answer-{{ $faq->id }}" class="accordion-collapse collapse" data-bs-parent="#faq-{{ $loop->parent->index }}">
                                        <div class="accordion-body">{!! nl2br(e($faq->answer)) !!}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <p class="text-center">Aucune question pour le moment. Contactez-nous pour toute demande.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
