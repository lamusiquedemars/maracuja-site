@extends('layouts.site')

@section('content')
    @php($media = $item->primaryMedia())
    <article class="site-section catalogue-detail">
        <a class="back-link" href="{{ route('catalogue.index') }}">← {{ $collection->plural_label }}</a>
        @if($item->subtitle)<p class="eyebrow">{{ $item->subtitle }}</p>@endif
        <h1>{{ $item->title }}</h1>
        @if($media)<img class="catalogue-detail__image" src="{{ $media['url'] }}" alt="{{ $media['alt'] ?? $item->title }}">@endif
        @if($item->description)<div class="catalogue-detail__description">{!! nl2br(e($item->description)) !!}</div>@endif
        <p class="catalogue-detail__price">{{ $item->price_label ?: ($item->price_amount ? number_format((float) $item->price_amount, 2, ',', ' ').' €' : 'Prix sur demande') }}@if($item->availability) · {{ $item->availability }}@endif</p>
        @if(filled($item->attributes))
            <dl class="catalogue-detail__attributes">
                @foreach($item->attributes as $label => $value)<dt>{{ $label }}</dt><dd>{{ $value }}</dd>@endforeach
            </dl>
        @endif
    </article>
@endsection
