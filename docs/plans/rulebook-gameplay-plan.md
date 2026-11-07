# Plan : rulebook des règles de gameplay

Statut : PLAN mis à jour 2026-11-06 après cadrage (stockage en base, édition en ligne en markdown pur). Rien codé.

## Objectif

Une référence unique des règles de gameplay :
- les scénaristes la lisent (version claire, lecture seule) sur une page du site ;
- l'équipe règle (ROLE_REGLE) l'édite en ligne ;
- le développement et les tests s'appuient dessus, via un cache local de fichiers markdown.

## Décisions prises (2026-11-06)

1. **Source de vérité : la base de données.** Pas de fichiers versionnés comme référence.
2. **Cache local** : une commande exporte la base en fichiers `docs/rulebook/<domaine>.md` (un fichier par domaine), en lecture seule et ignorés par git. Ils servent à consulter les règles hors ligne et à travailler (dev, écriture de tests, session Claude) sans passer par le site. Le cache porte un en-tête "généré le ... depuis la base, ne pas modifier".
3. **Un fichier par domaine** (donc une entité domaine en base, exportée en un fichier).
4. **Droits**
   - ROLE_SCENARISTE : lecture seule, version claire uniquement, règles publiées uniquement.
   - ROLE_REGLE : lecture et édition de tout (claire, détail technique, statuts, règles BUGUÉ et À CONFIRMER, brouillons).
   - ROLE_ADMIN : contrôle total (hérite de ROLE_REGLE), plus gestion des domaines et restauration d'historique.
   - Les règles BUGUÉ, À CONFIRMER et TODO ne sont visibles que de ROLE_REGLE et ADMIN.
5. **Édition : markdown pur, pas de TinyMCE.** Pas de HTML saisi par l'utilisateur.

## Éditeur markdown

Oui, un éditeur simple existe. Recommandation : **EasyMDE** (éditeur markdown sur CodeMirror, sans WYSIWYG, avec barre d'outils minimale et aperçu côte à côte, ~ 300 Ko, chargé via importmap). On édite du texte markdown brut, ce qui évite le HTML sale de TinyMCE. Repli si on veut zéro dépendance : un `<textarea>` avec aperçu rendu côté serveur par `league/commonmark`.

Contre les caractères non imprimables et le copier/coller :
- **Normalisation côté serveur à l'enregistrement** (service unique, testé) : suppression des caractères de largeur nulle (U+200B à U+200F, U+2060, U+FEFF), des caractères de contrôle hors tabulation et saut de ligne, remplacement de l'espace insécable (U+00A0) et des espaces Unicode exotiques par une espace normale, normalisation NFC, fins de ligne en `\n`, suppression des espaces en fin de ligne. Guillemets et apostrophes typographiques : conservés (décidé).
- **Rendu sûr** : `league/commonmark` avec `html_input: strip` et `allow_unsafe_links: false`, donc aucun HTML ni script injectable.
- **Signal visuel** : à la sauvegarde, message "N caractères invisibles retirés" pour que l'auteur sache ce qui a été nettoyé.
- Optionnel : un bouton "coller en texte brut" dans l'éditeur.

## Modèle de données (proposition)

- `RulebookDomaine` : id, code (`xp`, `territoire`...), titre, position.
- `RulebookRegle` : id, domaine, code stable (`XP-03`), titre, position, `texteClair` (markdown), `detailTechnique` (markdown : formule, edge cases), `statut` (VALIDE, BUGUÉ, FIXÉ, TODO, HARDCODÉ, À CONFIRMER), `testsRefs` (liste de chemins ou ids de tests), publiée oui/non, updatedAt, updatedBy.
- `RulebookRevision` : historique à chaque enregistrement (règle, auteur, date, anciens textes, motif), pour relire et restaurer. Utile puisque l'édition est en ligne et que git ne couvre plus l'historique.

Le découpage en règles individuelles (et non un gros texte par domaine) permet de filtrer par statut et par rôle, de lier un test à une règle précise et de tracer les amendements.

## Étapes

0. **Cadrage** : fait (voir décisions). Reste à valider le modèle de données et l'éditeur.
1. **Modèle** : entités, migration, repository, voter `RulebookVoter` (lecture claire / lecture complète / édition), tests unitaires du voter.
2. **Service de normalisation du markdown** + rendu sûr commonmark, avec tests (caractères invisibles, NBSP, HTML injecté, liens `javascript:`).
3. **Import initial** : commande `app:rulebook:importer` qui lit `gameplay-rules-precise.md`, crée domaines et règles (ids stables, statuts, ancres tests). Mode simulation puis `--apply`. Les blocs "version claire" sont à rédiger ensuite à la main par l'équipe règle (champ vide au départ, la règle n'est pas publiée tant que la version claire est vide).
4. **Page de consultation** `/rulebook` : sommaire par domaine, recherche, filtre par statut (réservé ROLE_REGLE). Scénaristes : version claire des règles publiées uniquement.
5. **Édition en ligne** (ROLE_REGLE) : création, modification, réordonnancement, changement de statut, publication, historique et restauration. Éditeur EasyMDE.
6. **Cache local** : commande `app:rulebook:exporter` (un fichier par domaine dans `docs/rulebook/`, gitignoré), option `--domaine`. Appelée automatiquement après chaque enregistrement de règle (domaine concerné) et au déploiement (tout régénérer, à ajouter à `deploy.yml`). Éventuellement bouton "télécharger le cache" (zip) pour ROLE_REGLE. Mention du cache dans `CLAUDE.md` pour les sessions de dev.
7. **Lien tests** : `#[Group('XP-03')]` (ou `@see`) sur les tests de règles ; commande `app:rulebook:verifier` (règles VALIDE sans test, ids cités par un test mais absents de la base, règles sans version claire). Avertissement en CI d'abord.
8. **Processus** : toute évolution de règle commence par le rulebook, puis le test, puis le code ; revue périodique des règles TODO et À CONFIRMER (initiative notamment). Livraison d'abord sous ROLE_BETA_TEST.

## Points tranchés (2026-11-06, 2e passe)

- **Journal** : le journal d'actions existant (`log_action`) suffit comme trace, pas de notification. Chaque entrée renvoie à la version de la règle (révision) pour relire ce qui a changé.
- **Cache local** : régénéré automatiquement à chaque modification d'une règle (le domaine concerné seulement) et au déploiement (régénération complète). La commande `app:rulebook:exporter` reste disponible à la main.
- **Guillemets** : les guillemets et apostrophes typographiques sont conservés tels quels, la normalisation ne les touche pas.

## Bascule après l'import (décidé)

Une fois l'import fait (étape 3), on bascule sur le nouveau rulebook comme seule source de vérité. Il faudra alors le **vérifier** avant de figer l'ancien fichier :
- relecture domaine par domaine par l'équipe règle (formules, statuts, edge cases, ancres tests identiques à `gameplay-rules-precise.md`) ;
- comparaison automatique : nombre de règles et de sections importées contre l'ancien fichier, aucune perte ;
- ensuite seulement, `gameplay-rules-precise.md` est réduit à un pointeur vers le rulebook.
Cette vérification est une étape à part entière (3bis) du suivi.

## Reste ouvert

- Le fichier mémoire `gameplay-rules-precise.md` est figé après l'import (pointeur vers le rulebook) pour éviter deux sources de vérité.

## Suivi

| Étape | Statut |
|---|---|
| 0 Cadrage | FAIT 2026-11-06 |
| 1 Modèle + voter | CODÉ sur la branche `feat/rulebook` (3 entités, enum `RulebookStatut`, 3 repositories, `RulebookVoter`, migration `Version20261107122025` appliquée en local, tests unitaires). Non commité, non poussé. |
| 2 Normalisation + rendu sûr | CODÉ sur `feat/rulebook` : `MarkdownNormalizer` (renvoie `MarkdownNettoye` avec le nombre de caractères nettoyés), `MarkdownRenderer` (commonmark GFM, `html_input: strip`, liens dangereux neutralisés), 32 tests verts. Non commité. À brancher sur l'enregistrement à l'étape 5 (message « N caractères retirés »). |
| 3 Import initial | CODÉ sur `feat/rulebook` : `SpecParser`, `RulebookImportService`, commande `app:rulebook:importer <fichier> [--apply]` (simulation par défaut, ne remplace jamais une règle déjà en base). Testé sur la vraie spec : 23 règles, 13 domaines, appliqué en LOCAL seulement. 4 règles sans statut reconnu importées « À confirmer » (COND-01, GN-01, ORI-01, ORI-02), à trancher en 3bis. Le fichier doit être dans le dépôt monté dans le conteneur (pas `var/`). Non commité. |
| 3bis Vérification du rulebook importé, puis bascule (ancien fichier réduit à un pointeur) | TODO |
| 4 Page de consultation | CODÉ sur `feat/rulebook` : `RulebookController` (`/rulebook`, `/rulebook/regle/{code}`), `RulebookRegleRepository::findConsultables`, templates `templates/rulebook/`, entrée « Rulebook » dans le menu. Accès = ROLE_BETA_TEST **et** (ROLE_SCENARISTE ou ROLE_REGLE) ; ROLE_ADMIN n'hérite pas de la bêta, il faut donc lui ajouter ROLE_BETA_TEST. Scénariste : version claire des règles publiées de statut public ; équipe règle : tout, filtre statut, recherche dans le détail. Une règle non visible répond 404. Tests fonctionnels d'accès par rôle. Non commité. Plus tard : retirer la contrainte bêta. |
| 5 Édition en ligne | TODO |
| 6 Cache local (export) | TODO |
| 7 Lien tests + vérification | TODO |
| 8 Processus | CODÉ (docs/rulebook-processus.md, app:rulebook:revue, modèle de PR ; CODEOWNERS non créé : handles GitHub de l équipe règle à fournir) |
