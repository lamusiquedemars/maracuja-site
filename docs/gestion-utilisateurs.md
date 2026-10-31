# Utilisateurs et accès des sites Maracuja

Les comptes et leurs mots de passe appartiennent à chaque application Laravel.
Ils ne sont ni un module Site Core, ni des comptes Cremona ou Control. Chaque
site utilise la connexion et le profil Filament ; le profil exige le mot de
passe actuel pour le modifier. Le middleware de session vérifie le hachage à
chaque requête, et Filament limite les tentatives de connexion et de profil.

## Attribuer des fonctions

Dans **Réglages > Utilisateurs**, l'administrateur Maracuja crée ou modifie un
compte, choisit son profil et sélectionne les fonctions accessibles. Une liste
vide ne donne accès à aucune fonction répertoriée. Les fonctions dont le module
est inactif sont signalées : les cocher n'installe ni n'active le module. Les
adaptations propres à un site, comme Arcus ou les soutenances orales, restent
locales. Le tableau de bord agrégé est masqué quand une sélection explicite est
en place, car il comprend des statistiques de plusieurs fonctions.

Les profils sont des limites supplémentaires :

- **Administrateur Maracuja** : gère les comptes, les réglages globaux et les
  outils internes ; il accède aux capacités installées et actives. Sa sélection
  individuelle ne le restreint pas.
- **Responsable du site** et **éditeur** : accèdent aux fonctions attribuées,
  sans accès à la gestion des comptes ni aux réglages internes. Le responsable
  du site et l'éditeur disposent actuellement des mêmes droits éditoriaux ;
  les cases décident des fonctions effectives.
- **Lecteur**, là où il existe : droits de consultation sur les fonctions
  compatibles, sans écriture. Les écrans Demandes, Conversations et Audience
  lui sont provisoirement refusés car ils contiennent des actions spécifiques
  qui ne sont pas toutes conçues pour la lecture seule.
- Sur **Marcos Túlio**, les profils historiques `content_editor` et
  `client_manager` sont conservés ; une sélection explicite peut combiner
  fonctions éditoriales et fonctions d'accueil client. Les réglages techniques
  et l'audience restent internes dans cette application. Aucun lecteur n'y est
  ajouté tant que ses écrans métier ne sont pas adaptés.

Tous les utilisateurs autorisés à entrer dans le panneau peuvent voir leur
profil et changer leur propre mot de passe. Seul l'administrateur Maracuja peut
créer, modifier, désactiver ou supprimer un autre compte. Un compte désactivé est refusé à
la connexion et aux requêtes suivantes du panneau. L'interface protège le
dernier administrateur actif et empêche l'auto-désactivation.

La suppression est définitive et demande une confirmation ; elle est proposée
depuis la liste et la fiche du compte. Un administrateur actif ne peut pas
supprimer son propre compte. La vérification est répétée en base pendant la
suppression afin de préserver au moins un administrateur actif. Les relations
de médias et de conversations configurées avec `nullOnDelete` conservent leurs
données mais perdent la référence au compte supprimé. Pour retirer un accès
sans supprimer le compte, utiliser sa désactivation.

## Compatibilité et mise en service

`content_permissions = null` conserve le comportement historique du profil ;
un tableau, même vide, exprime une sélection explicite. Chaque site conserve sa règle historique d’administration (`admin`, `is_admin`,
ou les deux selon l’application) : il faut attribuer
manuellement un profil client si un ancien administrateur est en réalité un
responsable client. Les migrations de la référence, d'Un Violon et d'Atelier
transforment l'ancien privilège exceptionnel lié à une adresse en rôle ou
indicateur explicite une seule fois. La décision d'accès ne dépend ensuite
plus d'une adresse. Sur Contempo, le rôle par défaut des nouveaux comptes devient
`viewer`, sans modifier les rôles déjà enregistrés. La réversion du schéma ne
révoque pas cette attribution,
car elle a pu être modifiée entre-temps.

Les cinq dépôts ont des historiques et des modèles métiers distincts. Ils
utilisent la même convention de permissions locales et le même parcours, avec
leurs adaptateurs propres ; la référence sert de modèle de maintenance. Aucun
package de comptes n'est créé dans Site Core. Avant toute publication, vérifier
la liste des comptes et leurs profils, sauvegarder la base, livrer le code et
exécuter les migrations du site concerné, puis tester un compte interne et un
compte client. Les migrations n'ont pas été exécutées sur les bases des sites
par ce travail.

Cremona reste propriétaire des objets métier et de leurs projections. Les
modules de médias, galeries, contenus, événements et autres capacités restent
des packages ou des extensions installés séparément des comptes.
