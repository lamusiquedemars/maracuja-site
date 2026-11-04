# Plan directeur — Acquisition Maracuja

## 1. Objectif

Construire une capacité d'acquisition réutilisable qui permette à un client, en particulier Marcos Túlio, de comprendre dans son propre tableau de bord :

> Combien avons-nous dépensé, combien de demandes avons-nous reçues, lesquelles étaient pertinentes, combien sont devenues clientes et quel revenu peut être attribué à la campagne ?

Le système doit réduire fortement le besoin d'ouvrir Google Ads ou Google Analytics. Ces outils restent les sources techniques détaillées, tandis que le tableau de bord Maracuja présente les informations nécessaires aux décisions courantes.

## 2. Principes

1. Le site reste capable de recevoir et qualifier des demandes sans Cremona.
2. Cremona devient la source de vérité commerciale et le cockpit multi-client.
3. Google Ads reste la source de vérité des dépenses et performances publicitaires.
4. GA4 reste la source de vérité du comportement agrégé sur le site.
5. Aucune donnée personnelle ou juridique sensible n'est envoyée à Google Analytics ou au dataLayer.
6. Le client reste propriétaire de ses comptes Google ; Maracuja reçoit les accès nécessaires.
7. La première version prépare et observe ; toute création réelle reste en pause et toute activation exige une confirmation explicite, un journal d’audit et les portes de lancement du runbook.
8. Chaque étape doit apporter un résultat visible et testable avant de passer à la suivante.

## 3. Répartition des responsabilités

### Maracuja CMS Starter

Le starter contient le module réutilisable `Acquisition` :

- configuration du tracking ;
- insertion de Google Tag Manager ;
- consentement cookies ;
- événements standardisés ;
- capture des paramètres d'attribution ;
- enrichissement des formulaires, rendez-vous et conversations ;
- client API vers Cremona ;
- tableau de bord local ;
- mode autonome limité si Cremona n'est pas connecté.

### Marcos Túlio

Marcos est le premier pilote réel du module :

- configuration en portugais et en BRL ;
- événements sur les CTA WhatsApp, téléphone, formulaire, consultation et chatbot ;
- parcours commercial adapté au cabinet ;
- dashboard compréhensible par Marcos ;
- validation avec de vraies campagnes et de vrais retours commerciaux.

Les améliorations génériques validées chez Marcos doivent ensuite remonter dans le starter.

### Cremona

Cremona contient la partie centrale `Acquisition Management` :

- intégrations Google sécurisées ;
- comptes publicitaires par organisation ;
- synchronisation des campagnes et métriques ;
- réception des demandes attribuées ;
- qualification et issue commerciale ;
- revenus attribués ;
- calcul des CPL, CPQL, CAC et ROAS ;
- API de synthèse pour les sites ;
- vision multi-client Maracuja ;
- plus tard, renvoi des conversions réelles à Google Ads.

## 3.1. Chantier Cremona détaillé

Cremona n'est pas une simple destination technique des formulaires. Il devient le noyau métier de l'acquisition Maracuja. Son chantier est découpé en quatre livraisons visibles.

### Cremona A — attribution et résultat commercial

Objectif : relier une demande entrante à sa provenance et à son résultat réel.

- étendre `IncomingRequest` avec term, content, click IDs, landing page et referrer ;
- distinguer first touch et last touch ;
- conserver la méthode et le niveau de confiance de l'attribution ;
- ajouter consultation, client, perdu et motif de perte au parcours commercial ;
- enregistrer la date de conversion, le revenu attribué et la devise ;
- exposer ces informations dans les ressources Filament existantes ;
- compléter les widgets CRM par source et résultat.

Livrable visible : dans Cremona, Maracuja peut ouvrir une demande Marcos et voir d'où elle vient, si elle est qualifiée et ce qu'elle est devenue.

### Cremona B — campagnes et coûts

Objectif : disposer d'un registre commun des campagnes et de leurs résultats journaliers.

- créer `AcquisitionAccount` pour les comptes fournisseurs d'une organisation ;
- créer `AcquisitionCampaign` pour les campagnes locales et externes ;
- créer `AcquisitionMetric` pour les snapshots journaliers ;
- gérer fournisseur, identifiant externe, statut, budget, devise et dates ;
- permettre une saisie manuelle ou un import contrôlé avant la connexion API ;
- calculer CPC, CTR, CPL, CPQL, CAC et ROAS dans un service unique ;
- afficher un dashboard Acquisition par organisation.

Livrable visible : même sans API Google, Cremona peut montrer combien a été dépensé et rapprocher ce montant des leads et clients Marcos.

### Cremona C — connecteurs Google

Objectif : remplacer la saisie manuelle par une synchronisation fiable.

- stocker le `customer_id` dans l’intégration d’organisation et conserver les credentials d’agence chiffrés dans le coffre plateforme Cremona ;
- connecter le MCC Maracuja aux comptes clients autorisés, sans mot de passe client ;
- synchroniser campagnes, dépenses, impressions, clics et conversions Google Ads ;
- interroger GA4 pour les visites, événements et pages d'entrée utiles ;
- exécuter les synchronisations en jobs avec reprises et idempotence ;
- journaliser succès, erreurs, quotas et date de fraîcheur ;
- ne jamais appeler Google directement pendant le rendu d'un dashboard ;
- créer des campagnes complètes en pause et les activer seulement après confirmation humaine explicite.

Livrable visible : les chiffres publicitaires se mettent à jour automatiquement dans Cremona et dans le dashboard Marcos ; les campagnes validées peuvent être créées en pause depuis Cremona, sans diffusion accidentelle.

### Cremona D — boucle d'optimisation et cockpit agence

Objectif : utiliser les résultats commerciaux pour améliorer les campagnes et administrer plusieurs clients.

- préparer et envoyer les conversions qualifiées ou clientes à Google Ads ;
- garantir le consentement, l'idempotence et l'absence de données juridiques ;
- ajouter une vue comparative multi-organisation pour Maracuja ;
- détecter attribution cassée, absence de dépenses, chute de conversions ou données périmées ;
- produire des recommandations lisibles ;
- plus tard, proposer des actions Google Ads avec confirmation et journal d'audit.

Livrable visible : Maracuja peut piloter plusieurs clients dans Cremona, tandis que chaque client ne voit que ses propres résultats dans son site.

### Contrat entre un site et Cremona

Le contrat doit rester versionné et indépendant des écrans. Il comprendra progressivement :

- `POST /api/v1/incoming-requests` : création idempotente d'une demande attribuée ;
- `PATCH /api/v1/incoming-requests/{id}/commercial-status` : évolution commerciale si elle est saisie depuis le site ;
- `GET /api/v1/acquisition/summary` : synthèse d'une période pour le dashboard local ;
- `GET /api/v1/acquisition/campaigns` : résultats par campagne ;
- `GET /api/v1/acquisition/health` : état des connexions et fraîcheur des données.

Les routes de lecture renvoient uniquement les données de l'organisation authentifiée. Les sites ne reçoivent jamais les credentials Google ni les données d'un autre client.

### Maracuja Site

Le module sera distribué par Maracuja Site Core après validation du pilote. Chaque Maracuja Site gérera son activation et sa configuration sans bloquer la réalisation actuelle.

## 4. Flux cible

```text
Visiteur
  -> choix de consentement
  -> capture UTM / identifiant de clic / page d'entrée
  -> clic WhatsApp, téléphone, formulaire, agenda ou chatbot
  -> création serveur de la demande dans le site
  -> transmission idempotente à Cremona
  -> qualification par Marcos ou Maracuja
  -> consultation / client / perdu
  -> calcul des résultats commerciaux

Google Ads -> dépenses, clics et campagnes -> Cremona
GA4        -> visites et événements agrégés -> Cremona
Cremona    -> synthèse métier -> dashboard du site Marcos
```

## 5. Vocabulaire commun

- **Visite** : session observée sur le site.
- **Événement** : action non personnelle, par exemple un clic WhatsApp.
- **Demande ou lead** : personne ayant demandé un contact.
- **Lead qualifié** : demande correspondant réellement aux services du cabinet.
- **Consultation** : rendez-vous demandé ou réalisé selon le niveau de précision disponible.
- **Client** : demande ayant produit une relation commerciale confirmée.
- **Attribution** : lien raisonnable entre une demande et sa source d'acquisition.
- **CPL** : dépenses publicitaires divisées par le nombre de leads.
- **CPQL** : dépenses divisées par le nombre de leads qualifiés.
- **CAC** : dépenses divisées par le nombre de nouveaux clients.
- **ROAS** : revenu attribué divisé par les dépenses publicitaires.

## 6. Modèle de données minimal

### Configuration locale du site

- tracking activé ;
- GTM Container ID ;
- consentement activé ;
- URL de politique de confidentialité ;
- référence du site dans Cremona ;
- état et date de la dernière synchronisation ;
- fuseau horaire et devise d'affichage.

Les secrets Google et tokens OAuth ne sont pas stockés dans chaque site.

### Attribution attachée à une demande

- attribution first touch et last touch ;
- `utm_source`, `utm_medium`, `utm_campaign` ;
- `utm_term`, `utm_content` ;
- `gclid`, `gbraid`, `wbraid` ;
- page d'entrée ;
- referrer ;
- date de première et dernière visite connue ;
- méthode d'attribution et niveau de confiance.

### Campagne dans Cremona

- organisation ;
- fournisseur ;
- compte externe ;
- identifiant externe de campagne ;
- nom, type et statut ;
- budget configuré ;
- dates de début et fin ;
- devise ;
- dernière synchronisation.

### Métriques journalières

- date et campagne ;
- impressions ;
- clics ;
- coût ;
- conversions publicitaires ;
- événements principaux observés ;
- leads locaux ;
- leads qualifiés ;
- consultations ;
- clients ;
- revenus attribués.

Les métriques Google sont conservées sous forme de snapshots journaliers afin d'éviter des appels API à chaque affichage du dashboard.

## 7. Événements standardisés V1

- `whatsapp_click`
- `phone_click`
- `contact_form_start`
- `generate_lead`
- `appointment_request`
- `chat_start`
- `chat_contact_request`

Chaque événement accepte seulement des paramètres contrôlés tels que l'emplacement du bouton, la page et le type de formulaire. Les champs libres du visiteur sont interdits.

`generate_lead` est émis uniquement après confirmation par le serveur de la création de la demande.

## 8. Tableau de bord Marcos V1

### Ligne de synthèse

- montant dépensé ;
- clics publicitaires ;
- demandes ;
- qualifiées ;
- consultations ;
- clients ;
- revenus attribués.

### Indicateurs calculés

- CPC ;
- taux clic vers demande ;
- CPL ;
- CPQL ;
- CAC ;
- ROAS.

### Explication humaine

Le dashboard ne montre pas seulement des nombres. Il produit des phrases simples, par exemple :

- « Les annonces ont généré des visites mais peu de demandes : vérifier la landing page. »
- « Une part importante des demandes n'est pas qualifiée : revoir les termes de recherche. »
- « Les demandes sont pertinentes mais deviennent rarement clientes : examiner le suivi commercial. »
- « L'attribution de plusieurs demandes est inconnue : vérifier le tracking. »

Ces indications restent des diagnostics, pas des modifications automatiques.

### Détails utiles

- résultats par source ;
- résultats par campagne ;
- évolution dans le temps ;
- principales pages d'entrée ;
- termes de recherche à examiner ;
- santé du tracking et dernière synchronisation ;
- liste des demandes nécessitant une qualification.

### Accès à Google

Des liens profonds permettent d'ouvrir Google Ads ou GA4 lorsqu'une analyse avancée est nécessaire. L'utilisation courante doit rester possible depuis Maracuja.

## 9. Phases de réalisation

### Phase 0 — cadrage opérationnel

Résultat : comptes, propriétaires et règles de données documentés.

- identifier le compte Google propriétaire de Marcos ;
- créer ou confirmer le MCC Maracuja ;
- lister les accès GA4, GTM, Ads, Search Console et Profil Google ;
- confirmer la devise BRL et le fuseau de Cuiabá ;
- valider les définitions de « qualifié », « consultation », « client » et « revenu » ;
- choisir le niveau de Consent Mode après validation de la politique applicable.

Compréhension associée : différence entre compte client, MCC, GTM, GA4 et Google Ads.

### Phase 1 — fondation Acquisition dans le starter

Résultat : un site starter sait activer le tracking proprement et capturer une attribution.

- créer `app/Modules/Acquisition` ;
- ajouter configuration, migration et ressource Filament ;
- ajouter le composant de consentement ;
- ajouter l'API JavaScript de tracking ;
- créer les composants ou conventions de CTA suivis ;
- capturer et conserver first touch et last touch ;
- ajouter tests unitaires, fonctionnels et de non-divulgation de PII ;
- documenter l'installation.

Compréhension associée : différence entre visite, événement, lead et attribution.

### Phase 2 — application Marcos Túlio

Résultat : les demandes Marcos contiennent une origine exploitable.

- intégrer le module du starter ;
- configurer GTM et consentement ;
- instrumenter WhatsApp, téléphone, contact, agenda et chatbot ;
- enrichir les inquiries ;
- compléter le workflow commercial ;
- ajouter une vue de qualification rapide ;
- vérifier manuellement chaque événement et chaque parcours.

Compréhension associée : pourquoi un clic WhatsApp n'est pas encore un client.

### Phase 3 — liaison Marcos vers Cremona

Résultat : chaque demande est transmise une seule fois et devient pilotable dans le CRM.

- étendre le contrat API de Cremona pour toute l'attribution ;
- ajouter un outbox ou job de transmission côté site ;
- garantir l'idempotence et les reprises après erreur ;
- mapper site, organisation, formulaire et canal ;
- synchroniser ou exposer les changements de statut ;
- journaliser les transmissions sans contenu sensible inutile.

Compréhension associée : distinction entre donnée de trafic et résultat commercial.

### Phase 4 — premier dashboard sans dépendance Google Ads API

Résultat : Marcos voit déjà les sources, demandes, qualifications et clients.

- ajouter les widgets Filament locaux ;
- afficher les données Cremona disponibles ;
- permettre temporairement la saisie ou l'import contrôlé des dépenses ;
- afficher le niveau de complétude de l'attribution ;
- tester avec des données de démonstration puis réelles.

Cette phase permet de livrer de la valeur même si le developer token Google est encore en attente.

Compréhension associée : lecture d'un entonnoir visite -> lead -> qualifié -> client.

### Phase 5 — intégration GA4 et Google Ads dans Cremona

Résultat : coûts et métriques se mettent à jour automatiquement.

- créer les intégrations chiffrées par organisation ;
- mettre en place OAuth et le developer token ;
- synchroniser les campagnes et métriques chaque jour ;
- récupérer les rapports GA4 nécessaires ;
- mettre en cache les agrégats ;
- surveiller quotas, erreurs et fraîcheur ;
- exposer une API de synthèse au dashboard Marcos.

Compréhension associée : CPC, CTR, CPL, CAC et ROAS, avec leurs limites.

### Phase 6 — boucle de conversions réelles

Résultat : Google peut apprendre quels clics produisent des leads qualifiés ou des clients.

- enregistrer consentement et identifiants de clic nécessaires ;
- définir les actions de conversion ;
- envoyer les conversions de manière idempotente ;
- associer date, valeur et devise ;
- gérer erreurs et doublons ;
- vérifier les résultats dans Google Ads.

Cette phase est activée après validation juridique et opérationnelle.

Compréhension associée : différence entre conversion web et conversion commerciale hors ligne.

### Phase 7 — généralisation

Résultat : la capacité peut être installée sur un nouveau site avec peu de code spécifique.

- remonter les corrections de Marcos dans le starter ;
- séparer traductions et configuration métier ;
- stabiliser le contrat de version entre sites et Cremona ;
- distribuer le module via Maracuja Site Core ;
- définir les offres dans lesquelles le module est disponible ;
- créer une checklist d'installation client.

## 10. Hors périmètre de la première version

- modification automatique des budgets ;
- création automatique d'annonces ;
- ajout automatique de mots-clés négatifs ;
- attribution parfaite des appels téléphoniques ou conversations WhatsApp externes ;
- remplacement complet de Google Ads ou GA4 ;
- CRM juridique ou gestion de dossiers ;
- envoi de données sensibles à des outils marketing.

## 11. Critères d'acceptation du pilote Marcos

Le pilote est réussi lorsque :

1. les choix de consentement sont respectés ;
2. les sept événements V1 peuvent être vérifiés ;
3. un formulaire n'émet `generate_lead` qu'après succès serveur ;
4. une demande conserve son origine sans PII dans Analytics ;
5. Cremona reçoit la demande exactement une fois ;
6. Marcos peut qualifier une demande simplement ;
7. le dashboard affiche dépenses, leads, qualifiés, clients et KPI sur une période ;
8. la fraîcheur et les erreurs de synchronisation sont visibles ;
9. aucun chiffre absent n'est présenté comme zéro ;
10. un contrôle manuel permet de rapprocher un échantillon du dashboard avec Google Ads et les demandes réelles.

## 12. Méthode de travail et formation

Chaque phase suit le même rythme :

1. expliquer en langage simple le concept du jour ;
2. montrer où il apparaît dans le parcours Marcos ;
3. implémenter une petite tranche ;
4. la tester techniquement ;
5. la vérifier visuellement ou fonctionnellement ensemble ;
6. noter ce que le dashboard permettra désormais de décider ;
7. seulement ensuite passer à la tranche suivante.

La formation suit le produit réel. Elle ne demande pas d'apprendre Google Ads ou Analytics en bloc avant de commencer.

## 13. Première tranche de travail

La première tranche de code est limitée à :

- squelette du module `Acquisition` dans le starter ;
- modèle de configuration ;
- insertion conditionnelle de GTM ;
- API d'événements sans PII ;
- capture locale des paramètres d'attribution ;
- tests correspondants.

Elle ne touche pas encore aux campagnes Google et ne nécessite aucun secret de production.
