@extends('layouts.site', [
    'seoTitle' => $item->seo_title ?: $item->title,
    'seoDescription' => $item->seo_description ?: $item->excerpt,
    'seoImage' => $media['url'] ?? null,
])

@section('content')
    <x-site.hero :eyebrow="$catalogueLabel" :title="$item->title" :subtitle="$item->subtitle" variant="page" />

    <x-site.breadcrumb :items="[
        ['label' => $catalogueLabel, 'url' => route('catalogue.index')],
        ['label' => $item->title],
    ]" />

    <x-site.section container="readable">
        <article class="stack stack--lg">
            @if ($media)
                <figure class="media-figure">
                    <img src="{{ $media['url'] }}" alt="{{ $media['alt'] ?? $item->title }}">
                </figure>
            @endif
            @if ($item->description)
                <div class="prose">{!! $item->description !!}</div>
            @elseif ($item->excerpt)
                <p class="prose">{{ $item->excerpt }}</p>
            @endif
            @include('site.catalogue.item-meta', ['item' => $item])
            @if (filled($item->attributes))
                <dl class="stack stack--sm">
                    @foreach ($item->attributes as $label => $value)
                        <div>
                            <dt><strong>{{ $label }}</strong></dt>
                            <dd>{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </article>
    </x-site.section>
@endsection
