@extends('layouts.site', [
    'seoTitle' => $catalogueLabel . ' - ' . $settings->site_name,
    'seoDescription' => $collection?->description,
])

@section('content')
    <x-site.hero :eyebrow="null" :title="$catalogueLabel" :subtitle="$collection?->description" variant="page" />

    <x-site.section>
        <div class="stack stack--lg">
            @if ($collection?->categories->isNotEmpty())
                <nav class="cluster" aria-label="Filtrer le catalogue">
                    <x-site.button
                        :href="route('catalogue.index')"
                        :variant="$category === '' ? 'primary' : 'secondary'"
                        :aria-current="$category === '' ? 'page' : null"
                        size="small"
                    >Tout voir</x-site.button>
                    @foreach ($collection->categories as $catalogueCategory)
                        <x-site.button
                            :href="route('catalogue.index', ['categorie' => $catalogueCategory->slug])"
                            :variant="$category === $catalogueCategory->slug ? 'primary' : 'secondary'"
                            :aria-current="$category === $catalogueCategory->slug ? 'page' : null"
                            size="small"
                        >{{ $catalogueCategory->name }}</x-site.button>
                    @endforeach
                </nav>
            @endif

            @if ($items->isEmpty())
                <p>{{ $category === '' ? 'Aucune fiche publiée pour le moment.' : 'Aucune fiche ne correspond à ce filtre.' }}</p>
            @else
                <x-site.grid columns="3">
                    @foreach ($items as $item)
                        @php($media = $item->primaryMedia())
                        <article @class(['card', 'card--media' => $media !== null])>
                            @if ($media)
                                <div class="card__media">
                                    <img src="{{ $media['url'] }}" alt="{{ $media['alt'] ?? $item->title }}" loading="lazy">
                                </div>
                            @endif
                            <div @class(['card__content' => $media !== null, 'stack' => $media === null])>
                                @if ($item->subtitle)
                                    <p class="card__kicker">{{ $item->subtitle }}</p>
                                @endif
                                <h2><a class="card__link" href="{{ route('catalogue.show', $item->slug) }}">{{ $item->title }}</a></h2>
                                @if ($item->excerpt)
                                    <p class="card__body">{{ $item->excerpt }}</p>
                                @endif
                                @include('site.catalogue.item-meta', ['item' => $item])
                            </div>
                        </article>
                    @endforeach
                </x-site.grid>
            @endif
        </div>
    </x-site.section>
@endsection
