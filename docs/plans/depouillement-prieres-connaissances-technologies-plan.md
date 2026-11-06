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

## Questions pour l'utilisateur au démarrage

- Les prières ne peuvent pas être ajoutées : sur quel écran exactement, avec quel message (voir Phase 0) ?
- Les connaissances, technologies et langues se saisissent-elles comme les sorts (ajout direct à la validation, sans trigger), ou faut-il
  consommer le trigger correspondant (technologie d'Artisanat, langues de Littérature) ?
- Pour une langue apprise en jeu, quelle source enregistrer (`LangueSourceType`) : une source existante, ou une nouvelle « apprise en jeu » ?
- L'enseignant d'une connaissance, d'une technologie ou d'une langue doit-il être contrôlé (inscrit au GN, niveau expert) comme pour une
  compétence, ou simplement consigné ?
- Faut-il aussi les recettes alchimiques en texte (déjà couvertes par la liste des potions) et les « maladies » du formulaire Google ?
