# Retour de jeu déclaré par le joueur et export CSV pour le wargame : plan pour une session ultérieure

Écrit le 2026-10-24, après le chantier du dépouillement d'enveloppes (étapes 0 à 12 faites, voir `depouillement-enveloppe-reprise.md`).
Rien n'est codé. Ce document doit permettre de démarrer une session neuve sans relire toute la conversation.

## But

Permettre à un joueur de déclarer lui-même son retour de jeu dans LarpManager, en remplacement du formulaire Google « PJ » et
du formulaire « groupe » qui complètent aujourd'hui l'enveloppe, et permettre au rôle `ROLE_WARGAME` d'exporter ces déclarations en
CSV, dans un format qui corresponde à ce que l'import existant sait lire.

Le dépouillement d'enveloppes (ce qu'il y a PHYSIQUEMENT dans l'enveloppe) reste distinct : ici, c'est ce que le JOUEUR déclare
(compétence apprise, sorts, langues, descendants, renommée...). Les deux se recoupent sur certains points (sorts, prières, potions,
compétence apprise) : voir « Doublons avec le dépouillement ».

## Ce qui existe déjà (cartographié le 2026-10-24)

- **Un seul import réel : les fiches de retour de GROUPE** (`src/Service/FicheRetourGroupeImportService.php`, CSV ou XLSX). Il n'y a
  PAS d'import du formulaire joueur, ni d'onglet « Erreur apprentissage » dans le code (cette règle existe seulement dans la saisie orga
  et dans `DepouillementApprentissageService`). L'utilisateur parle de « l'import existant » : c'est celui des groupes, qui sert de
  modèle de calibrage.
  - Séparateur `,` guillemet `"`, UTF-8 (repli ISO-8859-1), pas de BOM lu. En-têtes normalisés (minuscules, sans accents) puis
    comparés par préfixe à `COLUMN_MAP` (`l.23-52`) : `submitted at`, `groupe concerne`, `nb de pieces d argent`, `nb de pieces d or`,
    `nb total d ingredients`, `nb total de potions`, `armement`, `chevaux`, `fruits leg`, `m simples`, `sel`, `betail`, `coton`,
    `gemmes`, `moutons`, `soie`, `bois`, `esclaves`, `ivoire`, `pierre`, `teinture`, `cereales`, `fourrures`, `m precieux`, `poisson`,
    `vin`, `commentaire`.
  - Le groupe est retrouvé par le PREMIER NOMBRE de la cellule `groupe concerne` (ex. « 21 Argos »), pas par un identifiant séparé.
  - Une ligne par groupe et par GN, une réimportation ÉCRASE (historique `FicheRetourGroupeHistory`, action `import`), pas de simulation.
  - Contrôleur `GnController::importFicheRetour` (`fiche_retour.import`, `ROLE_WARGAME`), modèle vierge `fiche_retour.template`
    (BOM UTF-8, `,`). Tout le code est dans `GnController.php` lignes 1064 à 1158.
- **Le rôle** : `Role::WARGAME` (`ROLE_WARGAME`), hérité par `ROLE_SCENARISTE`, `ROLE_ORGA`, `ROLE_REGLE` et donc `ROLE_ADMIN`. Aucun menu dédié :
  c'est un droit fonctionnel.
- **Exports CSV du projet** : style dominant `header('Content-Type: text/csv')` + `fputcsv($sortie, $ligne, ';')`, sans BOM. Il existe aussi
  un helper `$this->sendCsv(...)` appelé dans une dizaine de contrôleurs dont la DÉFINITION est introuvable par recherche : à localiser
  avant de s'en servir (sinon écrire un petit service `ExportCsv`).
- **Entités qui reçoivent un retour** : renommée (`RenommeHistory`), langues (`PersonnageLangues` + `LangueSourceType`), connaissances
  et technologies (many-to-many sur `Personnage`), `vivant`, sorts, prières, potions, `PersonnageApprentissage`, lignées
  (`PersonnageLignee`), conceptions d'héritier (`Conception`, `HeritierService`). **Aucune entité maladie ni folie.** Constructions :
  `BaseConstruction` (étape 10 du dépouillement toujours en attente de règles de coût).
- **Aucune route joueur de saisie de retour.** Pour savoir à qui appartient une participation : `User::getParticipant($gn)` ; contrôler
  `participant->getUser() === utilisateur` à la main (pattern `PersonnageController` ligne 379). `PersonnageVoter` ne contrôle pas la
  propriété. `hasAccess()` est appelé dans `ParticipantController` mais sa définition n'a pas été retrouvée : ne pas s'en servir sans la lire.
- **Règles déjà codées à réutiliser** (voir `DepouillementApprentissageService`) : enseignant inscrit au GN et au moins expert
  (`PersonnageService::canTeachCompetence`), compétence accessible (`getApprentissageCompetences`), un apprentissage par fenêtre de GN
  (`PersonnageApprentissageRepository::hasApprentissage`), un apprentissage réduit le coût d'achat et n'ajoute pas la compétence.

## Contenu du formulaire joueur (d'après le formulaire Google PJ)

Colonnes connues (mémoire `fiche-retour-groupe-plan.md`, section J, sans aucune donnée personnelle) : personnage (nom et NUMÉRO),
groupe (« n° Nom »), mort (+ détails), sorti du jeu (+ détails), compétence apprise + numéro de l'enseignant, nouveaux sorts, langues,
recettes alchimiques, connaissances, technologies (chacun avec le n° de l'enseignant), constructions où le joueur a été artisan ou
expert, élèves enseignés (expert), gains et pertes de renommée, descendants conçus (nom + n° du partenaire), précisions (maladies,
folies), autres informations.

Formulaire GROUPE (complément) : groupe représenté, numéros de personnage des 6 titres (Suzerain, Chef de Guerre, Diplomate,
Intendant, Navigateur, Éminence Grise, un même numéro pouvant être répété), champ libre, et nouvelles constructions (n° de
l'expert). Les compteurs de ressources du groupe sont DÉJÀ couverts par l'enveloppe et par l'import des fiches de groupe.

## Décisions de conception proposées (à confirmer avec l'utilisateur au démarrage)

1. **Nouvelle entité `RetourJoueur`** (une par participant et par GN), et NON une extension de `Depouillement`. Raison : le dépouillement
   est lié à une enveloppe et à un dépouilleur ; ici le propriétaire est le joueur, avec un statut propre.
   - Champs : `participant`, `gn`, `statut` (`BROUILLON`, `SOUMIS`, `VALIDE`, `REJETE`), `donnees` (JSON structuré comme
     `ContenuJoueur`), `remarqueJoueur`, `remarqueValidateur`, dates de soumission et de validation, `validePar`, historique
     (calqué sur `DepouillementHistory`).
   - Un seul retour par participant et par GN, modifiable tant qu'il n'est pas soumis, puis verrouillé (le wargame peut le renvoyer en
     brouillon avec un motif).
2. **Accès joueur** : page « Mon retour de jeu » depuis la liste de ses participations (`/user`, « Mes grandeurs natures »), contrôle
   explicite de la propriété. Fenêtre de saisie ouverte et fermée par GN (date limite, comme « avant le 14/09 23h59 »).
3. **Parcours par étapes sur mobile**, même présentation que le dépouillement (étapes, `data-lm-unsaved`, confirmations en `<dialog>`).
   Étapes proposées : Mon personnage (mort, sorti du jeu), Ce que j'ai appris (compétence et enseignant, sorts, langues, recettes,
   connaissances, technologies), Mes élèves, Descendants, Constructions, Renommée, Précisions, Récapitulatif et envoi.
4. **Application à la validation**, par un rôle `ROLE_WARGAME` (ou scénariste, comme `DepouillementVoter`), avec les MÊMES services que le
   dépouillement. Extraire ce qui est dans `DepouillementApprentissageService` (compétence apprise, sorts, prières, potions) vers un service
   commun que les deux utilisent, pour ne pas dupliquer les règles. Ajouter pour les langues (source `LangueSourceType`), connaissances,
   technologies, renommée (`RenommeHistory`, explication `LH<gn> - <motif>` idempotente), descendants (créer une `Conception` en attente
   du scénariste, jamais l'héritier directement), mort (même traitement que `DepouillementFinOpusService::deces`).
   Tout ce qui est incohérent est signalé en anomalie sans bloquer, ce qui ne respecte pas une règle ferme (enseignant non expert) n'est
   pas appliqué, comme pour l'apprentissage du dépouillement.
5. **Contrôles à la saisie** : numéros de personnage vérifiés contre les inscrits du GN avec auto-complétion (réutiliser
   `DepouillementEnveloppeResolver`), âge et genre pour un descendant (réutiliser `ConceptionRegles`).
6. **Maladies et folies** : pas d'entité. Les garder en texte libre dans `donnees` et dans l'export, sans les appliquer, tant que
   l'utilisateur n'a pas défini le modèle.
7. **Constructions** : en texte libre (ou numéro d'expert) jusqu'à ce que les règles de coût de l'étape 10 soient fournies.

## Doublons avec le dépouillement

Un même sort ou une même compétence peut être saisi dans l'enveloppe ET dans le formulaire en ligne. Règles à valider :
- le résultat appliqué est idempotent (un sort déjà connu est ignoré, un apprentissage déjà enregistré pour le GN est ignoré), donc pas de
  dégât, mais le wargame doit voir « déjà appliqué par le dépouillement » dans le récapitulatif de validation ;
- ne PAS demander deux fois la même chose au joueur : le formulaire en ligne peut afficher en lecture seule ce que le dépouillement
  validé a déjà appliqué pour ce joueur ;
- le dépouillement reste la source de vérité pour les objets, ressources et étiquettes ; le formulaire en ligne pour ce que le joueur
  déclare (enseignants, élèves, descendants, renommée, précisions).

## Export CSV pour le rôle wargame

Exigence de l'utilisateur : l'export doit CORRESPONDRE à l'import, pour pouvoir échanger les fichiers dans les deux sens.

**Format retenu (celui de l'import des groupes, pas le style `;` dominant des autres exports)** : UTF-8 AVEC BOM (comme le modèle vierge
`fiche_retour.template`), séparateur `,`, guillemet `"`, première ligne d'en-têtes dont la normalisation (minuscules, sans accents)
est lue par préfixe, comme `COLUMN_MAP`. Raison : ré-importable sans adaptation et lisible par Excel avec le BOM.

Deux exports, accessibles depuis la page du GN pour `ROLE_WARGAME` :

1. **Export des fiches de groupe** (déjà couvert par l'import) : écrire l'EXPORT symétrique de `FicheRetourGroupeImportService` avec exactement
   les en-têtes de `COLUMN_MAP` et `groupe concerne` rempli en « n° Nom ». Ajouter une colonne `groupe_numero` en entier seul (c'était
   déjà prévu dans `fiche-retour-groupe-plan.md`, « Modèle de fichier d'import amélioré ») et faire lire cette colonne en priorité par
   l'import, avec repli sur l'ancien extracteur de nombre.
2. **Export des retours joueurs** (nouveau) : une ligne par `RetourJoueur`. Colonnes calquées sur le formulaire PJ, avec le NUMÉRO de
   personnage en colonne dédiée (jamais déduit d'un libellé) : `submitted at`, `personnage_numero`, `personnage_nom`, `groupe_numero`,
   `groupe_nom`, `mort`, `mort_details`, `sorti_du_jeu`, `sorti_details`, `competence_apprise`, `competence_enseignant_numero`,
   `sorts`, `langues`, `recettes`, `connaissances`, `technologies` (chaque liste en `libellé (enseignant n°N)` séparés par `|`),
   `constructions`, `eleves_enseignes`, `renommee_gain`, `renommee_perte`, `descendants` (`nom (partenaire n°N)` séparés par `|`),
   `precisions`, `autres`, `statut`, `valide_par`, `valide_le`. Pas d'e-mail ni de nom de joueur par défaut (données personnelles) : une
   option explicite pour le wargame si nécessaire.
3. **Import du retour joueur (optionnel, phase 2)** : un import symétrique de l'export 2 pour qu'un wargame puisse corriger un lot en
   tableur puis le réimporter, avec simulation puis `--apply` comme les autres commandes de reprise du projet.
4. **Test de cohérence indispensable** : exporter puis réimporter, et vérifier que l'import relit exactement ce que l'export a écrit
   (aller-retour sur les fiches de groupe dès la première étape, car ce code existe déjà).

## Étapes proposées

0. Questions à poser à l'utilisateur (voir plus bas) puis création du fichier mémoire de suivi (règle : tout plan débuté a un fichier
   mémoire avec statut).
1. Export des fiches de groupe + colonne `groupe_numero` + test d'aller-retour avec l'import existant. Petit, utile tout de suite, valide
   le format et le helper CSV (localiser ou créer `sendCsv`).
2. Entité `RetourJoueur`, historique, statuts, migration, repository.
3. Service d'application commun (extraction de `DepouillementApprentissageService`), tests d'intégration inchangés pour ne rien casser.
4. Formulaire joueur par étapes, contrôle d'accès, fenêtre de saisie par GN, brouillon, soumission.
5. Écran de validation pour le wargame (liste des retours soumis du GN, détail, anomalies, valider ou renvoyer avec motif).
6. Export CSV des retours joueurs + test de forme.
7. Optionnel : import du retour joueur avec simulation et `--apply`.
8. Réservé à la bêta (`ROLE_BETA_TEST`) comme le dépouillement, ouverture progressive.

## Pièges connus (à relire avant de coder)

- `public/assets` est servi tel quel en local : après un changement CSS ou JS, `rm -rf public/assets && php bin/console asset-map:compile`.
- Pas de `Co-Authored-By` dans les commits, pas de tiret cadratin dans les textes ajoutés, mago (format puis lint) avant chaque commit.
- Les tests de formulaires Doctrine partagent le cache de choix dans un même noyau : on ne peut pas tester deux listes successives.
- La base de test n'a ni genres ni tranches d'âge : `tests/Factory/GenresDeReference.php` insère les genres 1 et 2 au besoin.
- Un enseignant doit être inscrit au MÊME GN que l'élève (la règle est appliquée à la validation du dépouillement, pas à la saisie).
- Données personnelles : ne jamais copier un export réel dans le dépôt ni la mémoire.

## Questions ouvertes pour l'utilisateur

- Le formulaire en ligne REMPLACE-t-il le formulaire Google, ou les deux coexistent-ils pendant un GN de transition ?
- Qui valide : seulement `ROLE_WARGAME`, ou aussi le scénariste ? Et qui peut rouvrir un retour validé ?
- Les descendants créent-ils une `Conception` en attente (proposé) ou la déclaration reste-t-elle une simple information ?
- Faut-il le formulaire groupe (titres et constructions) dans la même session, ou seulement le formulaire joueur ?
- Format exact attendu par le wargame à l'export joueur : les colonnes ci-dessus sont déduites du formulaire PJ, pas d'un fichier réel.
- Maladies, folies, constructions : texte libre acceptable pour une première version ?

## Ajouts demandés le 2026-10-26

- **Lien fiche PJ <-> retour PJ** : la fiche du personnage pointe vers son retour de jeu du GN et le retour renvoie vers la fiche.
- **Même lien côté retour de groupe** : à faire à partir de la fiche de retour du groupe, par le chef de groupe, avec la liste des retours de ses membres.
- **Compteur de retours attendus par groupe** : nombre de participants du GN connu, donc « reçus / attendus » affiché sur le groupe.
- **Clôture d'un groupe incomplet** : statut « pas de retour » ou « n'a pas participé » pour un participant, avec motif, afin de pouvoir clôturer un groupe
  dont tous les retours ne viendront pas (le compteur compte ces cas comme traités).
- Le suivi du dépouillement (page paginée avec recherche) est décrit dans `depouillement-enveloppe-reprise.md` et doit partager le même compteur.
