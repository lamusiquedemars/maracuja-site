# Backlog UX — Audience et campagnes

## Principe directeur

Le client travaille avec des abonnés, segments et campagnes. Brevo est une
infrastructure d'envoi : son nom, ses listes techniques, ses synchronisations
et son canal ne doivent pas faire partie du parcours normal.

## À concevoir

- Masquer **Réglages → Brevo** aux rôles client ; le réserver aux
  administrateurs techniques.
- Retirer les libellés et statuts « Brevo » de l'interface client.
- Masquer l'identifiant de campagne fournisseur et remplacer « Transmise à
  Brevo » par un statut métier, par exemple « Envoi lancé ».
- Faire du bouton de test un vrai test via le canal configuré, ou le renommer
  explicitement s'il passe par le transport technique local.
- Lors de la création ou modification d'un segment, synchroniser
  automatiquement en arrière-plan lorsque la configuration est valide ;
  afficher un état métier simple en cas d'échec et notifier l'administrateur.
- Conserver une action de resynchronisation manuelle dans un espace technique,
  pas dans la liste des segments client.
- Déterminer automatiquement le canal de campagne depuis la configuration du
  site. Si Brevo est le canal configuré, ne pas proposer de sélecteur de
  canal au client.
- Distinguer la planification métier de l'ordonnancement fournisseur : le
  client choisit éventuellement « envoyer maintenant » ou « programmer »,
  sans notion de date transmise à Brevo.
- Remplacer le couple sélecteur de date / heure par un choix plus naturel :
  maintenant, demain matin, prochain jour ouvré, ou date et heure précises.
- Repenser le statut brouillon : le conserver comme information de cycle de
  vie, mais le présenter clairement et sans aspect technique grisé.

## Décision en attente

Choisir entre synchronisation implicite systématique et demande de validation
technique pour les très grands segments ou les cas en erreur.
