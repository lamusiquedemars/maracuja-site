# Vérifier le build cloud depuis le Terminal LWS (sans SSH entrant)

Ce script utilise le **HTTPS sortant** de LWS, vérifié avec `curl https://api.github.com` (HTTP 200). Il récupère le build GitHub Actions préparé dans le dépôt privé `maracuja-control`, contrôle l'archive SHA-256 et affiche les noms de migrations ajoutées ou absentes.

**Aucune installation** : le site public, `current`, `releases`, `shared/storage`, `.env` et la base de données ne sont pas modifiés. Les fichiers téléchargés dans `/tmp` sont effacés à la fin du contrôle.

## Authentification limitée

Créer un **Fine-grained personal access token** sous le compte `lamusiquedemars`, avec l'accès au **seul dépôt `maracuja-control`** et la permission **Actions → Read-only**. Aucun accès d'écriture, aucune permission sur les autres dépôts. Sa valeur est demandée **de manière masquée dans le Terminal cPanel** lors de l'exécution : ne jamais la copier en ligne de commande, dans un fichier de production ou dans un chat.

## Sur cPanel LWS → Terminal

Une fois ce script intégré à `main` :

```bash
curl -fsSL https://raw.githubusercontent.com/lamusiquedemars/maracuja-site/main/scripts/check-cloud-release.sh -o /tmp/maracuja-check-cloud-release.sh

bash -n /tmp/maracuja-check-cloud-release.sh

bash /tmp/maracuja-check-cloud-release.sh \
  43071276404 \
  967d5ffb273e062baa25316473d99ac357b66baa
```

La commande demande le jeton GitHub sur une ligne masquée. Elle télécharge l'artefact du build #43071276404, compare les empreintes et inspecte les noms des migrations. Le build de test expire le **18 novembre 2026** : après cette date, préparer un nouveau build et reprendre ses références. Le téléchargement dépend également de la connectivité HTTPS de LWS vers l'hébergement d'artefacts de GitHub, et pas seulement vers `api.github.com`.

## Interprétation du résultat

- **OK** : l'accès privé GitHub, l'archive et son SHA sont vérifiés. **Cela n'autorise aucune publication.**
- **401/403/404** : vérifier le jeton et son accès Actions: Read au dépôt privé, sans le partager.
- **Erreur réseau sur le ZIP** : vérifier le suivi des redirections HTTPS vers le stockage des artefacts GitHub.
- **Migrations supplémentaires** : définir leur traitement. Ne pas déclencher de migration automatiquement.
- **Alerte stockage non partagé** : le publieur actuel exige `current/storage` partagé, or la release LWS observée le 11 novembre 2026 utilise un stockage local à la release. Avant une vraie publication, préserver ces fichiers et adapter la livraison plutôt que forcer `current`.

## Préparer la release sans publication

Après un contrôle réussi, la même commande accepte l'option explicite `--stage` :

```bash
bash scripts/check-cloud-release.sh 43071276404 967d5ffb273e062baa25316473d99ac357b66baa --stage
```

Le script retélécharge et vérifie le build, compare cette fois **le contenu** de chaque migration (pas uniquement les noms), refuse les migrations divergentes, extrait la candidate sous `failed/<date>_<sha12>/`, relie son `.env` à `shared/.env` et son `storage` à `shared/storage`, puis inscrit le SHA-256 de l'archive dans un marqueur privé. Il ne modifie pas la release active, le lien `current`, la base ou le contenu partagé.

Le `storage` de l'ancienne release est un dossier indépendant, mais l'observation LWS du 11 novembre a montré que **les médias et les sauvegardes sont déjà dans `shared/storage`** ; le `rsync -ani` n'a pas trouvé de nouveaux médias dans l'ancienne release. **Ne pas synchroniser l'ensemble de `storage`** : cela déplacerait des métadonnées, caches et liens sans nécessité. Garder la release originale intacte pour un rollback.

L'option `--stage` prépare des fichiers dans `failed/` (donc consomme des inodes), mais n'exécute pas le déploiement. Une fois le résultat réel examiné, la bascule nécessite une commande distincte explicitement validée, avec contrôle des médias/contacts et possibilité de retour arrière.
