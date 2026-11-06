# Connexion unifiée site + Cremona

Lorsque le raccordement est activé, le site reste le point d'entrée et le
référentiel d'identité de ses utilisateurs. Les variables suivantes relient le
site à Cremona :

```env
CREMONA_SSO_ENABLED=true
CREMONA_SSO_ISSUER=https://cremona.maracujadigital.fr
CREMONA_SSO_CLIENT_ID=identifiant-technique-du-raccordement
CREMONA_SSO_LOGOUT_SECRET=secret-partagé-entre-les-deux-serveurs
```

L'utilisateur ouvre toujours `https://domaine-du-client/admin` et saisit son
adresse e-mail et son mot de passe **du site**. Le site vérifie ce mot de passe
dans sa propre base, puis demande à Cremona, par un échange serveur à serveur
signé et très court, les raccourcis et droits métier de ce même utilisateur.
Le mot de passe ne quitte jamais le site et n'est ni copié ni synchronisé dans
Cremona.

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

Il n’existe ni URL de retour libre, ni mot de passe recopié, ni jeton durable
conservé par le site. Un échec ne ferme aucune session par accident :
l’utilisateur revient au site et recommence sa connexion si nécessaire.

La connexion comme la déconnexion fédérée ne dépend ni de `Origin` ni de
`Referer`, variables selon les navigateurs. Le site signe ses demandes avec une
preuve HMAC liée au raccordement et à un état éphémère. Le secret est propre au raccordement,
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

Pour terminer une connexion, l'utilisateur doit avoir un compte local actif
sur le site **et** être membre actif de l'organisation raccordée dans Cremona,
avec la même adresse e-mail. Les comptes et leurs rôles restent créés par
l'administrateur de chaque application : le raccordement ne crée jamais un
accès métier par simple correspondance d'e-mail.

Le pilote Demo Maracuja est publié. La recette commence par
`https://site.maracujadigital.fr/admin`, avec un membre actif de
**Demo Maracuja**.

La procédure d'activation complète, y compris les clés persistantes et la
recette sécurité, est conservée dans le dépôt Cremona :
`docs/sso-site-pilote.md`.
