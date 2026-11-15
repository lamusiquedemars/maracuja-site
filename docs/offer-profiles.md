# Offer Profiles

Maracuja Site peut être configuré par profil commercial. Le profil décrit le niveau de complexité vendu. Il ne remplace pas le cadrage fonctionnel et ne doit pas devenir une liste rigide de modules.

## Profils

```env
MARACUJA_OFFER=essence
MARACUJA_OFFER=signature
MARACUJA_OFFER=univers
```

## Essence

Profil minimal pour site vitrine simple.

Intention:

- une structure courte;
- peu de contenus vivants;
- une administration très limitée;
- aucun module métier spécifique.

## Signature

Profil vitrine plus complet.

Intention:

- plus de contenus éditoriaux;
- galerie, actualités ou articles si le projet le demande;
- plus de finitions visuelles;
- toujours sans module métier lourd.

## Univers

Profil cadré pour les sites avec module métier simple, catalogue, réservation, outil connecté ou besoin qui dépasse la vitrine éditoriale.

`Univers` ne signifie pas “tous les modules”. Il signifie qu’un module métier doit être conçu, adapté ou créé pour le client.

## Interrupteurs par module

Les modules restent activés par projet. Une offre peut contenir peu ou beaucoup de modules selon le besoin réel.

Exemple: vendre une Signature sans galerie, ou une Essence avec une annonce courte.

```env
MARACUJA_OFFER=signature
MARACUJA_MODULE_GALLERY=false
```

Le layout public, les routes protégées et les ressources Filament doivent passer par `App\Support\Modules::enabled()`.

## Outils développeur

Certains modules peuvent rester installés sans être visibles dans l’admin client.

`Pages` est dans ce cas: il peut servir à gérer des pages techniques pendant le développement, mais il n’est pas une promesse client et ne doit pas devenir un page builder.

```env
MARACUJA_DEV_PAGES_ADMIN=false
```
