@php
    $price = trim($item->price_label ?? '');
    if ($price === '' && $item->price_amount !== null) {
        $price = number_format((float) $item->price_amount, 2, ',', ' ') . ' €';
    }
    $availability = trim($item->availability ?? '');
@endphp

@if ($price !== '' || $availability !== '')
    <p class="card__body">
        @if ($price !== ''){{ $price }}@endif
        @if ($price !== '' && $availability !== '') · @endif
        @if ($availability !== ''){{ $availability }}@endif
    </p>
@endif
