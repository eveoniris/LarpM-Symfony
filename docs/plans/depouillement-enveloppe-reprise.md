# Dépouillement d'enveloppes : document de reprise

Écrit le 2026-10-23 pour reprendre le chantier dans une conversation neuve. Le suivi détaillé, étape par étape, avec toutes les
décisions de l'utilisateur, est dans la mémoire du projet : `fiche-retour-groupe-plan.md` (dossier mémoire de Claude Code,
indexé par `MEMORY.md`). Ce document en est la version courte, orientée "par où reprendre".

## But

Remplacer le dépouillement papier des enveloppes de retour de jeu (une enveloppe par joueur, une par groupe) par un parcours
guidé dans LarpManager : saisie par étapes, photos, étiquettes d'objets, contenu, validation par un scénariste. Les formulaires
Google (apprentissages, titres, constructions...) restent HORS périmètre.

Tout est réservé au rôle `ROLE_BETA_TEST` (attribué par le super admin). Un mode simulation (purgeable, sans aucun effet) permet
de tester sans toucher aux données réelles.

## État au 2026-10-23

- Branche : `feat/depouillement-enveloppe`, créée depuis `origin/main`. NON POUSSÉE, NON MERGÉE.
- 18 commits de code, le dernier est `7a086f0a`, suivis d'un commit de documentation (ce fichier et le prompt de reprise).
- Suite complète : 824 tests verts, mago (format et lint) propre sur les fichiers de la branche.
- Fichier non suivi `public/i.php` : il n'est pas de nous, ne pas l'ajouter ni le supprimer sans demander.

### Fait (étapes 0 à 8 du plan, plus extras)

| Étape | Contenu | Où regarder |
|-------|---------|-------------|
| 1 | Accès `ROLE_BETA_TEST`, lien depuis le détail d'un GN | `DepouillementController`, `templates/gn/detail.twig` |
| 2 | Entités `Depouillement`, `DepouillementHistory`, statuts, types | `src/Entity/Depouillement*.php`, `src/Enum/Depouillement*.php` |
| 3 | Mode simulation, purge (bouton et commande) | `DepouillementService`, `app:depouillement:purger-simulations` |
| 4 | Parcours en 5 étapes, reprise avec avertissement, groupe en dernier | `DepouillementParcoursController` |
| 5 | Photos (stockage hors public, droits par voter, partage au propriétaire) | `DepouillementPhoto*`, `DepouillementPhotoVoter` |
| 6 | Étiquettes orange et bleues, bannières, objet retrouvé par numéro et qualité | `EtiquetteRegles`, `DepouillementEtiquetteService` |
| 7 | Contenu joueur (6 actions de fin d'Opus, apprentissages, surplus) et groupe (24 cases, reliquat) | `ActionsFinOpusRegles`, `DepouillementContenuService`, `DepouillementGroupeService` |
| 8 | Validation définitive, renvoi motivé, événement dans `log_action` | `DepouillementValidationService` |
| extra | Style lm-design (verre poli) conditionnel à l'interface bêta | `templates/depouillement/*.twig` |
| extra | Résumé à relire dans le récapitulatif | `templates/depouillement/etape.twig` |
| extra | `/simplify` appliqué (CSRF factorisé, N+1, code mort) | commit `f2f8c1a7` |

Vérifié de bout en bout dans le navigateur local (compte de test du projet) : joueur, groupe, photo, validation.

### Fait pour l'étape 11 (héritier), partiellement

- Tranche d'âge « Enfant héritier » (0 an, non choisissable à la création) : migration de données `Version20261030100000` et
  graine `docker/db/initData/4-newData.sql`. Sans elle un nouveau-né n'est jamais vieilli (les tranches de départ commencent à 15 ans).
- Source de langue `LangueSourceType::PARENTS` (priorité de l'origine secondaire, comptée comme langue de base).
- Règles pures du tirage et de l'âge : `ConceptionRegles`, enum `ConceptionIssue` (testés).
- Entité `Conception` + `ConceptionRepository` + `ConceptionStatut` + migration `Version20261031100000`.
  La collection `Depouillement::getConceptions()` existe. RIEN d'autre : pas de service, pas de contrôleur, pas d'écran.

## Décisions fonctionnelles de l'étape 11 (ne pas les redemander)

- Vocabulaire : toujours « héritier », jamais « enfant » seul (les enfants de la partie enfants ont leur jeu, leurs fiches et
  leur groupe distincts ; aucun enfant n'est jouable en adulte).
- Tirage : un d20, de 1 à 20, pas de 0. 1 = conséquence pour les parents (le scénariste décide) ; 2 à 4 = échec ; 5 à 10 = fille ;
  10 à 15 = garçon (10 vaut pour les deux, le scénariste choisit) ; 16 à 19 = réussite, genre au choix ; 20 = jumeaux (une fille
  et un garçon). C'est une SUGGESTION : le scénariste confirme la création de la fiche, ou gère la création lui-même plus tard
  dans le jeu, et son choix est consigné. Le tirage est persisté, jamais re-tiré.
- Âge : on ne peut plus concevoir à partir de 45 ans (tranche « Mûr »). Aucune borne basse précisée.
- Création : par un écran du scénariste à la validation. Une simulation ne crée jamais d'héritier.
- Héritier créé : sans joueur (`user` nul), vieillit comme les autres, XP de création dès la naissance (le scénariste ajuste
  ensuite, comme pour un transfert après la mort d'un personnage), origine et langue des parents choisies par le scénariste.
- Classe « secrète » : non proposée à la création, impossible à conserver quand l'héritier participe (il faut la changer pour
  « reprendre le personnage »), modifiable ensuite.
- Attribution à un joueur : manuelle, par un orga ou un scénariste.

## Reste à faire, dans l'ordre

### 1. Étape 11a : enregistrer les conceptions

- `ConceptionService` : enregistrer depuis une enveloppe de joueur (le personnage de l'enveloppe est identifié par son numéro,
  le partenaire est saisi par son numéro de personnage). Contrôles : joueur seulement, saisie modifiable, partenaire inscrit
  à ce GN (réutiliser `DepouillementEnveloppeResolver::resoudre`), deux personnages différents, couple déjà enregistré refusé
  (`ConceptionRepository::findParCouple`, car les deux joueurs peuvent le déclarer). Âge : anomalie via `ConceptionRegles::anomalieAge`,
  et pas de tirage si un parent a 45 ans ou plus. Sinon tirer une fois (`ConceptionRegles::tirer`) et stocker tirage et issue.
  Consigner dans l'historique du dépouillement. `simulation` de la conception = celle du dépouillement.
- Contrôleur (même modèle que `DepouillementEtiquetteController`, `#[IsGranted('ROLE_BETA_TEST')]` sur la classe concrète),
  section « Conceptions » dans `templates/depouillement/_contenu_joueur.twig`, ligne dans le « Résumé à relire ».
- Tests : service (intégration), fonctionnel (formulaire), garde-fous (âge, doublon, saisie terminée).

### 2. Étape 11b : écran du scénariste et création de l'héritier

- Écran listant les conceptions en attente du GN (statut `EN_ATTENTE`), tirage et issue suggérée, anomalies.
  Actions : créer l'héritier (ou les deux), « gérer plus tard » (remarque obligatoire), clôturer (échec ou conséquence).
  Réservé aux rôles qui valident (`DepouillementVoter`) et bêta. Jamais d'héritier pour une simulation.
- NE PAS réutiliser `PersonnageService::createNewPersonnage` tel quel : `empty($ageReel)` traite 0 comme vide, il retombe sur
  `getNextSessionGn()`, ajoute un événement « Participation » et appelle `addClasseCompetencesFamilyCreation`. Écrire un chemin dédié.
- Champs obligatoires du personnage (NOT NULL) : `classe`, `age`, `genre`, `nom`. `territoire` (origine) est nullable en base mais typé non nul.
- À la création : âge = tranche « Enfant héritier », `ageReel` = 0, `user` nul, classe secrète, genre et nom choisis, territoire
  choisi par le scénariste ; XP = `Gn::getXpCreation()` avec les `ExperienceGain` (voir `PersonnageService` autour de la ligne 396) ;
  chronologie « Naissance » avec l'année de jeu du GN ; `PersonnageLignee` (parent1 de genre 1 ou 3, parent2 de genre 2 ou 3, lignée
  facultative, via la logique de `PersonnageController::addLigneeAction`) ; langue des parents avec `addLangueSource(..., LangueSourceType::PARENTS)`.
- Classe secrète : créer la classe, la masquer des choix à la création (`PersonnageType`, `enableCreation` ou équivalent à
  vérifier sur `Classe`), ajouter la garde à la reprise ou à la participation. À étudier : `PersonnageOldFindType` permet-il de
  choisir un personnage SANS user pour le rattacher ?
- Conserver dans `Conception` : statut, `decidePar`, `decideAt`, `heritiers` (identifiants créés), `remarque`.

### 3. Étape 12 : dispatch réel (à cadrer avec l'utilisateur)

Appliquer à la validation ce qui est saisi : sorts, prières, potions, apprentissages sur la fiche du joueur ; étiquettes à réinsérer
et objets au PC joueur vers la zone enveloppe du personnage de l'opus suivant (`personnage/{id}/detail/enveloppe`) ; renommée (+1
par 3 Soie) ; consommation de fruits et légumes (la commande de vieillissement n'en compte que deux au maximum, à vérifier
dans `PersonnageService::checkDieOfOldAge`). Prévoir un statut intermédiaire quand la session suivante n'est pas encore ouverte
(`DepouillementStatut::EN_ATTENTE_IMPORT` existe déjà). Contrôle futur : un objet qui existe en X exemplaires annoncé par X+1 joueurs.

### 4. En attente de l'utilisateur

- Étape 9 : rôle de dépouilleur et page de création de compte avec identité complète. À faire après la bêta.
- Étape 10 : constructions. L'utilisateur doit d'abord obtenir les règles finales de coût et de nature.
- Étape 13 : score d'initiative. Règles connues (à confirmer) : 1 point par pièce d'argent, 5 par ingrédient, 10 par ressource,
  une carte Poisson compte 2 ; le reliquat du groupe est déjà additionné, aucune formule n'est codée.

### 5. Merge

L'utilisateur veut un merge direct sur `main` (sans pull request) une fois l'étape 11 faite. Demander s'il faut aussi pousser `main`.

## Avant la mise en production

- Migrations à exécuter dans l'ordre : `Version20261026100000` (vient de main), `Version20261027100000`, `Version20261028100000`,
  `Version20261029100000`, `Version20261030100000` (DONNÉES : tranche d'âge), `Version20261031100000`.
- Réglages PHP sur OVH : `upload_max_filesize` d'au moins 12M et `post_max_size` d'au moins 64M (déjà dans `docker/php/php.ini` en local).
- `var/uploads` doit être inscriptible et survivre aux déploiements (photos des enveloppes).
- Tests de la base de TEST : ne PAS y exécuter la migration de données de la tranche d'âge (la base de test n'a aucune tranche, les
  tests créent les leurs). Les migrations de schéma s'y exécutent une par une avec `doctrine:migrations:execute --up`,
  car cette base n'est pas construite par les migrations.

## Pièges techniques déjà rencontrés

- `#[IsGranted]` placé sur une classe parente n'est PAS hérité par Symfony : chaque contrôleur concret doit porter l'attribut
  (`DepouillementAccesTest` le vérifie).
- `phpunit` : toujours `php -d memory_limit=2G ./vendor/bin/phpunit` sur un gros lot, sinon mémoire saturée.
- `BaseRepository::findAll()` trie par `label` : les repositories de ce module (sans `label`) le surchargent.
- Twig : pas de `{% for x in y if cond %}`, pas de fonction fléchée dans `set` ; utiliser `filter(x => ...)` et `map`.
- L'outil Bash retire les antislashs dans certains `sed` : modifier les fichiers avec l'outil d'édition quand le texte contient `\`
  (espaces de noms PHP). Un `sed` mal échappé avait cassé des `use`.
- mago : les dépassements de taille (méthodes, propriétés, complexité) se traitent par `// @mago-expect lint:<règle>` juste au-dessus
  de l'attribut ou de la méthode concernée ; une exception inutile est elle-même signalée.
- Pas de factories pour `Item`, `Objet`, `Quality` : les créer à la main (un `Objet` exige `Rangement` et `Localisation`, avec `precision`
  renseigné, et `Quality::getQualityValeurs()->clear()`).
- Le navigateur intégré : après un clic de connexion, attendre quelques secondes avant de naviguer, sinon la session n'est pas posée.
- Un `form.submit()` en JavaScript contourne les `confirm()` des formulaires.

## Commandes utiles

```bash
docker compose exec webserver php -d memory_limit=2G ./vendor/bin/phpunit
docker compose exec webserver ./vendor/bin/mago format <fichiers>
docker compose exec webserver ./vendor/bin/mago lint <fichiers>
docker compose exec webserver php bin/console doctrine:migrations:status
docker compose exec webserver php bin/console app:depouillement:purger-simulations --gn=7
git log --oneline origin/main..HEAD
```

Application locale : http://localhost (compte de test du projet, voir la mémoire `feedback-test-credentials.md`). Le GN de test est
le numéro 7 (LH7). Un personnage de ce GN : 5789, un groupe : 330.

## Données personnelles

Les exports des formulaires Google (PJ et groupe) et les fiches Word reçues contiennent des noms et des e-mails de joueurs réels.
Ne jamais les copier dans le dépôt ni dans la mémoire : seule leur structure est notée dans le plan.
