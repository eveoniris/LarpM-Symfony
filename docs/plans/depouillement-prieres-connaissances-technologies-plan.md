# Dépouillement : prières, connaissances, technologies et langues apprises (plan pour une session ultérieure)

Écrit le 2026-11-06. Rien n'est codé : des tâches sont en cours, ce plan est à exécuter plus tard.

## Signalement

On signale qu'on ne peut pas ajouter de prière, ni de connaissance, ni de technologie « qui auraient été apprises » dans le retour de jeu
(dépouillement d'enveloppe de joueur, étape Contenu, section « Appris pendant le jeu »).

## Constat (vérifié le 2026-11-06, lecture seule)

- **Connaissances, technologies et langues : manque réel, pas un bug.** `ContenuJoueur` ne porte que `sorts`, `prieres`, `potions`,
  `competenceApprise` et `enseignantNumero` ; `ContenuJoueurType` ne propose que ces champs. Aucune occurrence de Connaissance,
  Technologie ou Langue dans le dépouillement, et `DepouillementApprentissageService::appliquer` ne traite que sorts, prières, potions et
  compétence. Le formulaire Google « PJ » les demandait pourtant (nouveaux sorts, langues, recettes, connaissances, technologies, avec le
  numéro de l'enseignant).
- **Prières : le champ existe** (`ContenuJoueurType` ligne 125, liste avec recherche). Le blocage signalé n'est PAS reproduit en local
  (36 prières, aucune sans sphère). Cause à établir (voir Phase 0). Pistes connues :
  - `BasePriere::$sphere` est typée `Sphere` NON nullable mais la jointure n'a pas `nullable: false` : une prière sans sphère en base
    (cas « orpheline », déjà géré dans `PrieresCorrectionService`) peut lever une erreur d'hydratation et faire échouer tout le formulaire
    (le `choice_label` du formulaire tolère un `getSphere()` nul, mais pas l'hydratation) ;
  - le quota de prières par niveau (`AttributeType::$PRIERES`) n'est pas appliqué à l'ajout ;
  - ajout en double côté entité : `Priere::addPersonnage()` appelle déjà `Personnage::addPriere()`, et plusieurs appelants font les deux.
- **Comment le projet ajoute déjà ces éléments** (pour réutiliser les règles) :

| Élément | Ajout admin | Ajout joueur | Contrôles existants |
|---|---|---|---|
| Prière | `PersonnageController` `add.priere` (742-768) | aucun direct, attribution à l'achat de Prêtrise (`PretriseService`) | aucun doublon, aucun quota, pas d'XP |
| Connaissance | `add.connaissance` (629-654), log `ADD_CONNAISSANCE` | aucun | verrou groupe seulement, champ `contraintes` jamais contrôlé |
| Technologie | `add.technologie` (945-971) | route `technologie` (2054-2102), trigger `TECHNOLOGIE` donné par Artisanat niveaux 3 et 4 | trigger consommé, liste `getAvailableTechnologies` |
| Langue | `updateLangueAction` (4864-4958) | `langueCourante`, `langueAncienne`, `langueCommune` (1462-1650), triggers de Littérature | triggers, `getAvailableLangues`, dédoublonnage par rang de source dans `Personnage::addLangueSource` |

  Côté propriétaire des relations : Personnage pour prières, connaissances et technologies (donc `Personnage::addX` suffit à persister).
  Aucune de ces routes ne consomme d'XP.

## Plan

### Phase 0 : diagnostic des prières (avant tout code)

1. Demander au signalement : quel écran (étape Contenu du dépouillement, fiche personnage admin, autre), quel message, quel personnage,
   quel navigateur, et si le champ « Prières apprises » s'affiche vide, ne s'ouvre pas, ou renvoie une erreur après enregistrement.
2. Regarder `var/log/prod.log` au moment du signalement (erreur d'hydratation ou 500 sur `/gn/{gn}/depouillement/{id}/etape/contenu`).
3. En base de production, compter les prières sans sphère : `select count(*) from priere where sphere_id is null;` et les prières
   sans niveau ou sans libellé.
4. Reproduire en local en créant une prière sans sphère, puis en ouvrant l'étape Contenu.
5. Vérifier aussi la même action sur la fiche personnage admin (`add.priere`), qui a des redirections `303` mal placées
   (`PersonnageController` lignes 653, 763-766 et 966-969 : le code est dans le tableau de paramètres de route, donc le statut n'est pas appliqué).

### Phase 1 : correction des prières (selon le diagnostic)

- Si prières orphelines : rendre la jointure explicite (`nullable` cohérent avec le type, ou propriété `?Sphere`), garder « Sans sphère »
  dans le libellé, et proposer la commande de correction existante (`PrieresCorrectionService`) avant d'appliquer.
- Dans tous les cas : un seul appel pour ajouter une prière (soit `Priere::addPersonnage`, soit `Personnage::addPriere`) et un contrôle de
  doublon centralisé, repris par `PretriseService`, `PersonnageService::giveMissingPretrisePreieres`, `fix.priest` et le dépouillement.
- Quota par niveau : le signaler comme anomalie non bloquante à la validation (comme les autres contrôles du dépouillement), sans refuser.

### Phase 2 : connaissances, technologies et langues dans le dépouillement

Même méthode que pour les sorts, prières et potions (ajout direct à la validation, sans consommer de trigger, doublon ignoré,
incohérence signalée sans bloquer, simulation sans écriture). Fichiers concernés :

1. `ContenuJoueur` : `connaissances` (liste), `technologies` (liste) et `langues` (liste, avec la diffusion courante, ancienne ou commune
   si l'utilisateur veut la distinguer). `ContenuJoueurSerialiseur` : stocker des identifiants comme les autres listes.
2. `ContenuJoueurType` : champs avec recherche (`liste()`), libellés lisibles. Connaissances : publiques et non secrètes
   (`ConnaissanceRepository::findAllPublicOrderedByLabel`). Technologies : `PersonnageService::getAvailableTechnologies`. Langues :
   `getAvailableLangues` (non secrètes).
3. Gabarits `_contenu_joueur.twig` (formulaire, lecture seule) et récapitulatif de `etape.twig`.
4. `DepouillementApprentissageService` : trois méthodes sur le modèle de `sort()` et `priere()`. Connaissance et technologie :
   `Personnage::addConnaissance` et `addTechnologie`. Langue : `Personnage::addLangueSource` avec une source à choisir (voir questions).
   Ajouter `ResultatApprentissages` pour chacun (ajoutes, ignores, anomalies).
5. Enseignant : le formulaire Google demandait le numéro de l'enseignant pour ces éléments. Le conserver en champ facultatif et le
   consigner dans l'historique ; ne contrôler le niveau expert que si l'utilisateur le demande (aucune règle de ce type n'existe pour les
   connaissances et technologies aujourd'hui, seulement pour les compétences : `ControleApprentissage`).
6. `PiecesAttendues` : pas de changement.
7. Tests : un test d'intégration par élément (ajout, doublon ignoré, simulation sans effet), un test fonctionnel du formulaire.

### Phase 3 : cohérence avec le futur retour de jeu joueur

Le plan `retour-de-jeu-joueur-plan.md` reprend ces mêmes éléments (langues, connaissances, technologies avec enseignant). Écrire le service
d'application de la Phase 2 de façon à ce qu'il soit réutilisable tel quel par ce futur formulaire (un service commun, pas de copie).

## Points à surveiller

- Routes de langue joueur (`langueCourante`, `langueAncienne`, `langueCommune`) : pas de `#[IsGranted]` ni de `hasAccess` visibles, seul le
  trigger est testé. À vérifier à part (sécurité), hors de ce chantier.
- `add.priere`, `add.technologie` et `add.connaissance` ne contrôlent aucun doublon.
- Ne pas modifier `ContenuJoueurType` ni `DepouillementApprentissageService` pendant que d'autres tâches y touchent : vérifier la branche
  et `git status` avant de commencer.
- Pièges habituels : `rm -rf public/assets && php bin/console asset-map:compile` après un changement CSS ou JS ; dans les tests, la
  compétence est le côté propriétaire de `personnages_competences` ; pas de `Co-Authored-By` ; mago (format puis lint) avant chaque commit ;
  pas de tiret cadratin dans les textes ajoutés.

## Réponses de l'utilisateur (2026-11-06) et décisions qui en découlent

1. **Prières** : on indique seulement que la zone « Prières apprises pendant le jeu » semble inaccessible. Comme « inaccessible » évoque
   l'interface (liste à recherche qui ne s'ouvre pas, champ masqué ou recouvert) plus qu'une donnée, la **Phase 0 commence par un test
   Playwright** (le dépôt a déjà `tests/playwright/`, avec `auth.setup.ts` et un audit de style) : ouvrir l'étape Contenu d'un
   dépouillement, ouvrir la liste « Prières apprises », choisir une prière, enregistrer, recharger et vérifier qu'elle est conservée. PHPUnit
   ne voit pas le JavaScript (Tom Select) : seul un test navigateur tranche entre un problème d'interface et un problème de données. Le garder
   ensuite comme test de non-régression (mêmes scénarios pour sorts, potions, connaissances, technologies, langues).
2. **Source d'une langue apprise** : une LISTE des sources connues (`LangueSourceType`) avec un choix « Autre » qui ouvre un texte libre.
   Cas à prévoir : la compétence de niveau Maître qui « donne » une langue à un PJ, et des bonus en jeu (beau jeu de rôle, récompense de
   quête). À terme : ajouter ces sources à un enum (nouvelles valeurs de `LangueSourceType` ou un enum dédié aux bonus en jeu) et les décrire
   dans le rulebook (voir `rulebook-gameplay-plan.md`). Première version : la saisie « Autre » est conservée en texte, on ne crée l'enum que
   quand le rulebook aura fixé la liste. À étudier : où stocker ce texte (le troisième argument de `Personnage::addLangueSource`, `triggerTag`,
   ou un champ dédié) sans casser le dédoublonnage par rang de source.
3. **Enseignant et conditions d'apprentissage** : CONSIGNÉS pour l'instant, le temps d'avoir les règles. Ils vont dans un champ dédié, présenté
   au dépouilleur et surtout au validateur (scénariste), qui décide par élément : **OK, on applique** ou **KO, les conditions
   d'apprentissage ne sont pas réunies**, et complète le rapport final si besoin. Conséquence de conception : connaissances, technologies et
   langues ne sont PAS appliquées automatiquement à la validation comme les sorts ; chaque élément a un statut (à décider, accepté, refusé) et une
   remarque du validateur, et seuls les éléments acceptés sont écrits sur la fiche. À confirmer : faire de même pour les sorts, prières et
   la compétence apprise, ou seulement pour ces trois nouveaux types.
4. **Maladies** : oui, elles peuvent avoir une utilité. À inclure : en texte libre structuré dans le contenu (liste d'entrées avec une
   description), repris dans le récapitulatif et dans l'export CSV du futur retour de jeu joueur. Pas d'entité maladie tant que les règles
   ne sont pas définies (aucune n'existe aujourd'hui).

5. **Périmètre et rôles de la décision OK/KO (précisé le 2026-11-06)** : l'ajout direct (sans trigger) est retenu s'il reste simple, MAIS
   une validation reste nécessaire. Un utilisateur qui n'a que le rôle dépouilleur ne peut PAS appliquer ; un scénariste (validateur) le
   peut. La décision OK ou KO ne concerne que les champs pour lesquels des « conditions d'apprentissage » ont été récemment ajoutées :
   **connaissances et technologies uniquement**. Les sorts, prières, potions, la compétence apprise et les langues restent appliqués
   comme aujourd'hui (automatiquement à la validation). Cela remplace la portée plus large de la décision 3 (langues et autres types).
   Constat de code (2026-11-06) : `BaseConnaissance` a bien un champ `transmission` (conditions de transmission), `BaseLangue` a un champ
   `condition_apprentissage`, mais **`Technologie` n'a aucun champ équivalent** : à confirmer avec l'utilisateur (ajouter le champ à la
   technologie, ou ne présenter que la connaissance pour l'instant).

## Suivi d'avancement (à mettre à jour après chaque étape, voir la règle de suivi des plans)

| Étape | Contenu | Statut | Date | Remarques |
|---|---|---|---|---|
| 0a | Test Playwright de la zone « Prières apprises » | TODO | | partir de `tests/playwright/auth.setup.ts` |
| 0b | Diagnostic prières (log prod, requête SQL des prières sans sphère, reproduction locale) | TODO | | |
| 1 | Correction des prières selon le diagnostic, ajout unique et doublon centralisé | TODO | | |
| 2a | Champs connaissances, technologies, langues, maladies dans `ContenuJoueur`, le formulaire et le récapitulatif | TODO | | |
| 2b | Statut par élément (à décider, accepté, refusé) et décision du validateur avec remarque dans le rapport final, POUR CONNAISSANCES ET TECHNOLOGIES SEULEMENT, réservé au scénariste (le dépouilleur seul ne peut pas appliquer) | TODO | | affiche les conditions d'apprentissage (`transmission` de la connaissance ; rien côté technologie) |
| 2c | Source de langue : liste des `LangueSourceType` + « Autre » en texte libre | TODO | | enum et rulebook à terme |
| 2d | Application des éléments acceptés (connaissance, technologie, langue) et tests | TODO | | |
| 3 | Service commun réutilisable par le futur retour de jeu joueur | TODO | | voir `retour-de-jeu-joueur-plan.md` |

Réponses du 2026-11-06 enregistrées (dont la décision 5 sur le périmètre et les rôles) ; aucune étape démarrée.

## Questions encore ouvertes pour le démarrage

- La technologie n'a pas de champ de conditions d'apprentissage : en ajouter un (calqué sur `transmission`) ou ne gérer que la connaissance ?
- Les langues ont `condition_apprentissage` mais sont appliquées automatiquement (décision 5) : l'afficher à titre d'information seulement ?
- Quel rôle exact pour le validateur : ROLE_SCENARISTE seul, ou aussi un rôle admin ? (décision 5 : le dépouilleur seul ne peut pas)
- Quel message exact voit la personne qui signale la zone des prières (capture d'écran, navigateur), pour guider le test Playwright ?
- Les recettes alchimiques en texte restent couvertes par la liste des potions : à confirmer.
