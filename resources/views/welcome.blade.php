@extends('layouts.site')

@section('content')
    <x-site.hero
        title="Maracuja Site"
        subtitle="Starter Laravel et Filament pour sites vitrines administrables."
    />

    <x-site.section title="front system maison" intro="Cette vue utilise uniquement les composants et classes Maracuja. Elle reste disponible comme page de secours du starter.">
        <x-site.grid columns="3">
            <x-site.card title="CSS organise" kicker="Foundations">
                Tokens, base, typographie et primitives réutilisables.
            </x-site.card>
            <x-site.card title="Composants Blade" kicker="Systeme">
                Hero, sections, grilles, cartes et boutons partagent le meme langage.
            </x-site.card>
            <x-site.card title="Thèmes clients" kicker="Identité">
                Les couleurs, fontes et ambiances se règlent par variables CSS.
            </x-site.card>
        </x-site.grid>
    </x-site.section>
@endsection
