# G3c : retour de jeu déclaré par le joueur et le groupe, en complément du dépouillement

Écrit le 2026-11-09. Statut au 2026-11-10 : étapes 1, 5, 6 faites, 2, 3, 4 et 7 partielles, 8 non faite (voir « Suivi d'avancement »). Ce plan précise et remplace les décisions de conception de `retour-de-jeu-joueur-plan.md`
(qui garde l'inventaire du code et le détail de l'export CSV). Module de départ : le dépouillement d'enveloppes
(`depouillement-enveloppe-reprise.md`), déjà sur `main` et derrière `ROLE_BETA_TEST`.

## Décisions de l'utilisateur (2026-11-09)

- Le formulaire Google PJ et le formulaire Google groupe seront remplacés si le module en ligne fait mieux. Un autre formulaire peut
  subsister pour des besoins ponctuels sur des éléments de jeu éphémères, hors périmètre.
- Complément du retour d'enveloppe, jamais un doublon : le module en ligne réemploie le plus possible le dépouillement.
- `ROLE_BETA_TEST` est temporaire. À terme le joueur y a accès lui-même et ses retours sont validés.
- Le numéro de l'enveloppe est le numéro du PJ pour un retour joueur, le numéro du groupe pour un retour de groupe.
- Validation : la partie wargame (ordres de conquête) par le rôle wargame (`ROLE_WARGAME`), le reste par les scénaristes, comme aujourd'hui.
- Deux formulaires distincts, deux sessions de saisie : joueur d'abord, groupe ensuite.
- Retour de groupe : un seul retour partagé. Les rôles qui le remontent (chef de groupe, suzerain, chef de guerre ou stratège) peuvent
  tous le compléter jusqu'à l'envoi ; on ne bloque pas si un joueur s'arrête en cours de route (un orga peut saisir à leur place).
- Retour de groupe : les 6 titres sont des numéros de personnage (comme le formulaire Google), le détail des ordres est un champ texte.
- Ordres de conquête en phases (3 au maximum, pas obligatoires) : chaque phase est validée par le wargame ; tant qu'il reste des ordres
  disponibles la phase suivante s'ouvre, sinon il n'y a qu'une seule phase.
- Si l'enveloppe contient déjà un sort, une compétence ou une potion, le formulaire en ligne l'affiche en lecture seule, sans le redemander.
- Export CSV pour le responsable du wargame : gardé, dans une étape suivante.

## Ce que les exports des formulaires Google montrent (structure seulement)

- PJ (746 réponses sur LH7) : seuls structurés le numéro de personnage, le groupe, mort (oui/non), sorti du jeu (oui/non), la compétence
  apprise et le numéro de l'enseignant. Tout le reste est du texte libre de formats variables : sorts, langues, recettes, connaissances,
  technologies (avec enseignant), constructions, enseignements donnés, renommée, descendants, précisions, autres informations.
  Un onglet « Erreur apprentissage » liste les enseignants qui ne sont pas au moins Expert (règle déjà codée).
- Groupe (68 réponses) : groupe au format « n° Nom », 6 titres par numéro de personnage, un champ texte long (ordres, récapitulatifs
  de finances et de ressources, remarques).
- Ces fichiers contiennent des données personnelles : ne jamais les copier dans le dépôt ni la mémoire.

## Conception retenue

1. **UN SEUL dossier par GN, type et numéro : l'entité `Depouillement` existante, sans doublon de déclaration** (décision du
   2026-11-09, elle remplace l'idée d'une propriété `origine`). Le joueur ouvre ou complète le dossier de son numéro ; l'enveloppe,
   possiblement incomplète, est ensuite traitée sur le même dossier. Mêmes statuts, même historique, même simulation, même clé d'unicité.
   Chaque ligne déclarée (sort, compétence, descendant, renommée...) garde sa provenance (joueur ou dépouilleur) et une décision
   (en attente, confirmé, complété, refusé) dans `donnees`. Le validateur confirme, complète ou refuse ce que le joueur a déclaré, sans
   le ressaisir. Un dossier validé peut être rouvert (renvoi motivé existant) pour compléter, en gardant ce qui est déjà déclaré.
2. **Acteur et droit de modifier** : le joueur propriétaire du participant (contrôle explicite, comme les routes `ApiJoueur*`) peut
   créer et modifier son dossier tant qu'aucun autre acteur n'en a pris le relais. Le relais est la reprise qui existe déjà
   (`DepouillementService::reprendre`, avec avertissement) : dès qu'un dépouilleur ou un validateur prend le dossier, le joueur passe
   en lecture seule (un complément d'information du joueur pourra être proposé plus tard). Le voter de validation reste scénariste,
   wargame, admin. Pendant la bêta tout reste derrière `ROLE_BETA_TEST` ; l'ouverture aux joueurs se fait en retirant ce rôle de ces
   routes seulement, derrière un réglage par GN (fenêtre de saisie ouverte et fermée).
3. **Parcours joueur** (étapes propres à l'origine `declaration`, sans photos ni étiquettes) : Mon personnage (mort, sorti du jeu),
   Ce que j'ai appris, Mes élèves et constructions, Descendants, Renommée, Précisions, Récapitulatif et envoi.
   Réemploi : `ContenuJoueur` (circonstances de mort, apprentissages, actions), `ConceptionService` (descendants),
   `DepouillementApprentissageService` (règle de l'enseignant Expert à la validation, et dès la saisie).
4. **Données structurées ou texte** (proposition, à confirmer) : structuré seulement ce que les règles contrôlent : compétence et
   enseignant, sorts, langues, recettes (potions), connaissances, technologies choisis dans des listes, descendants avec numéro du
   partenaire. Texte libre : détails de sortie, renommée, constructions, enseignements donnés, précisions, autres. Les enseignements
   donnés n'ont aucun effet automatique. Langues, connaissances et technologies : cela rejoint
   `depouillement-prieres-connaissances-technologies-plan.md` (saisie aujourd'hui impossible), à faire une seule fois pour les deux modules.
5. **Parcours groupe** : titres par numéro de personnage (contrôle des inscrits du GN, `DepouillementEnveloppeResolver`), champ libre,
   nouvelles constructions (numéro de l'expert), ordres de conquête par phase (champ texte). Accès : le chef de groupe et les titres
   déjà connus ; un orga peut saisir à la place. Qui exactement est « chef de groupe » dans les données reste à vérifier à la lecture du code.
6. **Application à la validation** : les mêmes services que le dépouillement pour la compétence apprise, les sorts, prières et
   potions (idempotents : déjà connu = ignoré, signalé « déjà appliqué par le dépouillement »). À ajouter : langues
   (`LangueSourceType`), connaissances, technologies, renommée (`RenommeHistory`, explication idempotente), descendants (une
   `Conception` en attente du scénariste, jamais l'héritier directement), mort (même traitement que `DepouillementFinOpusService::deces`).
   Incohérence = anomalie signalée sans bloquer ; règle ferme non respectée (enseignant non Expert) = non appliqué.
7. **Maladies, folies, constructions** : texte libre, non appliqué, tant que le modèle n'est pas défini (étape 10 du dépouillement en attente).

## Suivi d'avancement (audit du code sur main, 2026-11-10)

PR #89 à #94 mergées, puis commits `ce1a9b09` et `818d33b0` (liens, `groupe_numero`, export sans joueur). Rien n'est déployé en prod
à cette date. Module toujours derrière `ROLE_BETA_TEST`.

| Étape | Statut | Reste à faire |
|---|---|---|
| 0 Fichier mémoire | FAIT | |
| 1 Socle (dossier joueur, relais, provenance, accès, tests) | FAIT | |
| 2 Retour joueur (parcours et champs) | PARTIEL | Descendants (aucun champ côté joueur) ; lecture seule de ce que l'enveloppe a déjà appliqué |
| 3 Validation et application | PARTIEL | Appliquer les descendants en Conception en attente ; vérifier l'explication de renommée « LH<gn> - motif » |
| 4 Retour de groupe (titres, ordres, validation wargame) | PARTIEL | EN ATTENTE du responsable wargame : phases d'ordres qui s'ouvrent une à une (les 3 champs restent affichés), champ constructions (n° expert) |
| 5 Liens, compteur, clôture | FAIT | Compteur reçus/attendus, clôture avec motif, fiche personnage vers retour (staff bêta), dossier vers fiche et membres, suivi vers fiche |
| 6 API joueur et groupe | FAIT | |
| 7 Export CSV | PARTIEL | `groupe_numero` FAIT ; colonne joueur retirée par défaut, état civil réservé ADMIN/GESTION avec `?etat_civil=1` FAIT ; reste : test de forme des retours joueurs, import du retour joueur (phase 2, optionnel) |
| 8 Ouverture aux joueurs | NON FAIT | Fenêtre de saisie par GN, retrait de `ROLE_BETA_TEST`. Décision utilisateur requise |

Décision du 2026-11-10 : les exports identifient le personnage (numéro, nom ou surnom), jamais le joueur ; l'état civil est une colonne
optionnelle pour les rôles de gestion. Le wargame contacte les joueurs avec ses autres droits.

Ordre conseillé pour la suite : descendants (2 et 3), puis phases d'ordres et constructions (après réunion avec le responsable
wargame), puis test de forme des exports, puis étape 8.

## Questions encore ouvertes

- Tranché le 2026-11-09 : structuré quand c'est géré par une entité ou une règle, le reste en texte libre géré par les responsables ;
  le joueur modifie tant qu'aucun autre acteur n'a pris le relais, puis lecture seule (ajouts d'information complémentaire à voir plus tard).
- Qui est « chef de groupe » dans les données, et comment s'applique la liste des titres du GN précédent ? À vérifier dans le code.
- À VOIR AVEC LE RESPONSABLE WARGAME (non tranché) : phases d'ordres de conquête sur un dossier de groupe unique, chaque phase porte sa propre validation wargame dans `donnees`
  (à détailler à l'étape 4).

## Pièges (hérités du dépouillement)

- `#[IsGranted]` d'une classe parente n'est pas hérité : chaque contrôleur concret le porte.
- Pas de `Co-Authored-By`, pas de tiret cadratin dans les ajouts, mago (format puis lint) avant commit.
- Test local : le conteneur monte `F:\dev\LarpM-Symfony` ; les worktrees ne sont pas montés (copier les fichiers pour tester).
- Le test `ApiRoutesMethodsTest` remplace chaque `{placeholder}` par 1 et exige 401 sans jeton : valider les valeurs dans le contrôleur.

## Questions à poser au responsable wargame

1. Phases d'ordres : combien de phases par GN, et à quel moment chacune s'ouvre-t-elle (après validation de la précédente, ou à date fixe) ?
2. La phase suivante n'est-elle ouverte que s'il reste des ordres à donner, ou toujours ?
3. Nouvelles constructions : le groupe déclare-t-il un numéro d'expert, et qui le valide ?
4. Qui est le chef de groupe pour saisir les ordres, et comment reprendre les titres du GN précédent ?
5. Les exports par personnage (sans joueur) suffisent-ils, ou faut-il l'import du retour joueur corrigé en tableur (phase 2) ?
