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

## Déconnexion de l’espace

La commande unique **Se déconnecter de l’espace** ferme les deux sessions :
celle du site et celle de Cremona. Le site crée un état court, aléatoire et
lié à sa session, puis le navigateur le transmet en POST à Cremona. Cremona
vérifie que la requête provient bien du site déclaré pour ce client OAuth et
que l’utilisateur central appartient toujours à l’organisation concernée. Il
ferme alors sa session et revient vers l’URL de fin de session préconfigurée ;
le site vérifie le même état et ferme sa propre session.

Il n’existe ni URL de retour libre, ni mot de passe recopié, ni jeton OAuth
conservé par le site. Un échec ferme au minimum aucune session par accident :
l’utilisateur revient au site et recommence sa connexion si nécessaire.

La déconnexion fédérée ne dépend ni de `Origin` ni de `Referer`, variables selon
les navigateurs. Le site signe la demande avec une preuve HMAC liée au client
OAuth public et à son état éphémère. Le secret est propre au raccordement,
conservé uniquement dans les fichiers d'environnement des deux serveurs et
jamais transmis au navigateur. Le retour reste fixe et ne dépend jamais du
navigateur.

## Adresses et rôles

| Personne | Point d’entrée à communiquer | Rôle |
| --- | --- | --- |
| Visiteur | Domaine public du client | Aucun accès à l’administration. |
| Membre ou administrateur de l’organisation | `https://domaine-du-client/admin` | Contenus du site et raccourcis métier autorisés. |
| Administrateur Maracuja | Espace plateforme Cremona | Organisations, droits, raccordements et sécurité. |

`/admin/sso/*` et `/site-portal/logout` sont des URLs techniques courtes,
non navigables et non communicables aux utilisateurs. Les ouvrir directement
doit être refusé : seul le bouton de l’interface lance le flux complet.

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
