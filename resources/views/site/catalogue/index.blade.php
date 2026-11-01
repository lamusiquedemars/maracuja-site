@extends('layouts.site')

@section('content')
    <section class="site-section catalogue">
        <p class="eyebrow">{{ $collection->plural_label }}</p>
        <h1>{{ $collection->name }}</h1>
        @if($collection->description)<p class="lead">{{ $collection->description }}</p>@endif

        @if($collection->categories->isNotEmpty())
            <nav class="catalogue-filters" aria-label="Filtrer le catalogue">
                <a href="{{ route('catalogue.index') }}" @class(['is-active' => $category === ''])>Tout voir</a>
                @foreach($collection->categories as $catalogueCategory)
                    <a href="{{ route('catalogue.index', ['categorie' => $catalogueCategory->slug]) }}" @class(['is-active' => $category === $catalogueCategory->slug])>{{ $catalogueCategory->name }}</a>
                @endforeach
            </nav>
        @endif

        <div class="catalogue-grid">
            @forelse($items as $item)
                @php($media = $item->primaryMedia())
                <article class="catalogue-card">
                    @if($media)<img src="{{ $media['url'] }}" alt="{{ $media['alt'] ?? $item->title }}">@endif
                    <div class="catalogue-card__body">
                        @if($item->subtitle)<p class="eyebrow">{{ $item->subtitle }}</p>@endif
                        <h2><a href="{{ route('catalogue.show', $item->slug) }}">{{ $item->title }}</a></h2>
                        @if($item->excerpt)<p>{{ $item->excerpt }}</p>@endif
                        <p class="catalogue-card__meta">{{ $item->price_label ?: ($item->price_amount ? number_format((float) $item->price_amount, 2, ',', ' ').' €' : 'Prix sur demande') }}@if($item->availability) · {{ $item->availability }}@endif</p>
                    </div>
                </article>
            @empty
                <p>Aucune fiche ne correspond à ce filtre.</p>
            @endforelse
        </div>
    </section>
@endsection
