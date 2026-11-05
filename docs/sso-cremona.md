# Pilote SSO Cremona

Ce site peut déléguer son administration à Cremona lorsque les variables
suivantes sont explicitement activées :

```env
CREMONA_SSO_ENABLED=true
CREMONA_SSO_ISSUER=https://cremona.maracujadigital.fr
CREMONA_SSO_CLIENT_ID=identifiant-public-du-client-oauth
CREMONA_SSO_REDIRECT_URI=https://site.maracujadigital.fr/admin/sso/callback
CREMONA_SSO_DEFAULT_SITE_ROLE=admin
```

Il s'agit d'un client OAuth public : l'identifiant n'est pas secret et aucun
mot de passe Cremona ne transite par le site. Le navigateur utilise un code
court et PKCE, puis le site établit sa propre session d'administration.

Les raccourcis de gestion affichés dans le menu viennent de Cremona ; ils sont
déjà filtrés par organisation, module actif et droits de l'utilisateur. Le
site ne crée jamais de droit Cremona.

Pour terminer une première connexion, l'utilisateur doit être membre actif de
l'organisation raccordée dans Cremona. Une même adresse e-mail déjà présente
dans le site mais non reliée à un identifiant Cremona est refusée : le
rattachement d'un compte existant doit rester une opération explicite.

Le pilote Demo Maracuja est publié. La recette commence par
`https://site.maracujadigital.fr/admin`, avec un membre actif de
**Demo Maracuja**.

La procédure d'activation complète, y compris les clés persistantes et la
recette sécurité, est conservée dans le dépôt Cremona :
`docs/sso-site-pilote.md`.
