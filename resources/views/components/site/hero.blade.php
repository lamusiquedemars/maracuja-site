@props([
'eyebrow' => config('maracuja.product_name', 'Maracuja Digital'),
'title',
'subtitle' => null,
'ctaUrl' => null,
'ctaLabel' => null,
'secondaryCtaUrl' => null,
'secondaryCtaLabel' => null,
'variant' => null,
'image' => null,
'media' => null,
])

<section
    {{ $attributes->class([
        'hero',
        'hero--' . $variant => $variant,
        'hero--image' => $image,
    ])->style([
        'background-image: url(' . $image . ')' => $image,
    ]) }}>
    <div class="hero__inner">
        <div class="hero__content">
            @if (! empty($eyebrow))
            <p class="eyebrow">{{ $eyebrow }}</p>
            @endif
            <h1>{{ $title }}</h1>
            @if (! empty($subtitle))
            <p class="hero__subtitle">{{ $subtitle }}</p>
            @endif
            @if ((! empty($ctaUrl) && ! empty($ctaLabel)) || (! empty($secondaryCtaUrl) && ! empty($secondaryCtaLabel)))
            <div class="hero__actions">
                @if (! empty($ctaUrl) && ! empty($ctaLabel))
                <a class="btn btn--primary" href="{{ $ctaUrl }}">{{ $ctaLabel }}</a>
                @endif
                @if (! empty($secondaryCtaUrl) && ! empty($secondaryCtaLabel))
                <a class="btn btn--secondary" href="{{ $secondaryCtaUrl }}">{{ $secondaryCtaLabel }}</a>
                @endif
            </div>
            @endif
        </div>

        @if ($media)
        <div class="hero__media">
            {{ $media }}
        </div>
        @endif
    </div>
</section>
