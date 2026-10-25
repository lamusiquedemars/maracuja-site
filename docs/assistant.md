# Assistant IA

Le paquet partagé `maracuja-digital/site-core-assistant` porte les contrats IA
et les réglages d’exploitation de l’assistant. Il est distinct des
Conversations de Cremona : celles-ci restent responsables des emails, contacts
CRM, attribution humaine et historique de correspondance.

## Activation

```dotenv
MARACUJA_MODULE_ASSISTANT=true
OPENAI_API_KEY=
OPENAI_ASSISTANT_MODEL=gpt-5.6-luna
OPENAI_ASSISTANT_REASONING_EFFORT=low
OPENAI_ASSISTANT_MAX_OUTPUT_TOKENS=600
```

Les secrets, le fournisseur, le modèle et les limites techniques restent dans
l’environnement contrôlé par Maracuja. Ils ne sont jamais configurables par un
client depuis l’administration.

## Réglages administratifs

La page **Réglages > Assistant IA**, réservée aux administrateurs, permet de
définir le périmètre de l’assistant, son ton, les informations minimales qu’il
peut demander, les conditions de handover et les canaux humains autorisés.
Ces réglages sont stockés dans `assistant_settings`, séparément des secrets.

## WhatsApp et voix

WhatsApp est une option générique et facultative de passage vers un humain. La
V1 ouvre uniquement un lien `wa.me` avec le texte prévu ; elle ne synchronise
ni messages, ni résumés, ni transcripts. WhatsApp Business sera un adaptateur
ultérieur, distinct du CRM.

La voix sera un autre canal du même Assistant, avec son propre adaptateur de
téléphonie, ses règles de consentement et de conservation.

## Handover vers Cremona

Après consentement explicite, le runtime de l’Assistant pourra créer dans
Cremona une demande avec un résumé structuré minimal. Il ne transmettra jamais
un transcript complet par défaut.
