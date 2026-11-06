# Plan : lien « détail » de la carte vers /territoire/{id} parfois inaccessible

Statut global : **PLAN écrit 2026-11-06, rien investigué, rien codé**

## Problème signalé

Sur la carte, un clic sur un territoire ouvre une fenêtre avec un descriptif. Le lien « détail » de cette fenêtre
pointe vers `https://<site>/territoire/:id`. Pour certains territoires, ce lien ne donne plus accès à la page,
alors que l'utilisateur connecté devrait pouvoir consulter les infos de base.

## Piste principale (retour du signaleur, 2026-11-06)

Le problème touche les territoires **sans groupe assigné**, pour les **joueurs connectés** (pas les scénaristes ni
les admins). Dès qu'un groupe est rattaché au territoire, les joueurs y accèdent sans souci.
=> Le contrôle d'accès de la page détail semble déduire le droit de lecture de l'appartenance au groupe du
territoire (ou d'une relation groupe -> territoire) et refuse quand il n'y a pas de groupe (`null`).
À confirmer à l'étape 1 (voter, `denyAccessUnlessGranted`, requête qui joint le groupe).

## Piste réorientée (retour du signaleur, 2026-11-06 : le zonage manque sur la carte)

Le problème réel : le territoire 438 n'est **plus zoné sur la carte**, donc on ne peut pas cliquer dessus. Il n'est listé
dans aucun de `world/countries.json`, `regions.json`, `fiefs.json` avec une géométrie exploitable.

Constats dans le code :
- `HomepageController::getWorldTerritoireGeoData` renvoie TOUS les territoires, répartis par hiérarchie
  (`findRoot` : sans parent, `findRegions` : avec parent et avec enfants, `findFiefs` : avec parent sans enfant). Aucun
  filtre sur le groupe.
- `templates/world.twig` (`loadCountries/loadRegions/loadFiefs`) n'ajoute le territoire à la carte que si
  `country.geom != null`. Un territoire dont la colonne `geojson` est `NULL` n'apparaît donc pas. En local : 24 territoires
  sur 398 ont `geojson` NULL.
- **La route d'enregistrement du zonage n'existe plus.** Le JS poste sur `world/countries/{id}/update`
  (`world.twig:1150,1176,1202`), mais `HomepageController::updateCountryGeomAction` (l.316) n'a **aucun `#[Route]`**
  (jamais migrée depuis la conversion Silex vers Symfony, commit `e7e95943`). Aucun autre code n'écrit `geojson`
  (`setGeojson` n'est appelé que par cette action morte). Dessiner ou modifier un zonage sur la carte ne peut donc plus
  être sauvegardé.

Hypothèse principale : 438 a un `geojson` NULL (ou jamais zoné) et le zonage ne peut plus être enregistré depuis la carte.
À confirmer en prod : `SELECT id, territoire_id, geojson IS NULL FROM territoire WHERE id = 438;`.

## Constats de l'étape 1 (2026-11-06)

- Route : `TerritoireController::detailAction` (`territoire.detail`, `/territoire/{territoire}`, chiffres uniquement).
  Garde : `#[IsGranted(Role::USER)]` puis `hasAccess(null, [ORGA, SCENARISTE, CARTOGRAPHE])`. Le contrôleur passe `null`
  comme territoire, donc **il ne dépend pas du groupe** : tout `ROLE_USER` passe (`CAN_READ` = `isGranted(USER)`).
- Lien de la carte : `templates/world.twig` (`displayInfoPanel`) construit `/territoire/` + `props.id`, où `props` vient du
  GeoJSON stocké sur le territoire (`getGeojson()`), affiché seulement si `is_granted('ROLE_USER')`.
- Le template `territoire/detail.twig` protège déjà l'absence de groupe (suzerain, lien groupe).
- Reproduction : test `tests/Functional/Territoire/TerritoireDetailAccesTest.php` (joueur simple, territoire sans groupe)
  **passe** (200). En local, compte joueur `email_1@noreply.com`, les territoires sans groupe testés (2, 3, 31, 36, 40)
  répondent 200. **Le bug n'est donc pas reproduit : l'hypothèse « contrôle d'accès lié au groupe » est infirmée côté
  contrôleur.**

- Exemple réel fourni : `https://larpmanager.eveoniris.com/territoire/438` (prod). En local, le territoire 438 existe
  (anonymisé, 1 groupe) et répond **200** pour un joueur simple : non reproductible avec la base locale.
- Côté carte, `props.id` vient bien de `world/fiefs.json` (`country.id`, ajouté en JS) et non du GeoJSON stocké
  (dont `properties` est vide) : l'hypothèse « mauvais id dans le GeoJSON » est donc écartée.
- `security.yaml` : aucune règle `access_control` sur `/territoire`.

Pistes restantes : GeoJSON dont `properties.id` ne correspond pas au vrai id (territoire copié/fusionné), données de
prod différentes de la base locale anonymisée (erreur 500 dans le template), ou compte joueur dans un cas particulier.
Il faut un exemple réel (id + code HTTP) avant d'aller plus loin.

## Hypothèses initiales (secondaires, à écarter ou confirmer)

1. Le contrôle d'accès de la route de détail (`#[IsGranted]`, `denyAccessUnlessGranted`, voter, `access_control`
   dans `security.yaml`) est plus strict que ce que la carte laisse entendre (rôle requis, ou règle liée au GN,
   au groupe, à la visibilité du territoire).
2. Une régression récente sur les rôles (ROLE_STATISTIQUE ajouté, ROLE_SCENARISTE n'hérite plus de
   ROLE_CARTOGRAPHE) a retiré l'accès à certains profils.
3. L'URL générée par la carte est mal formée pour certains territoires (id manquant, route différente, ancienne
   route `territoir` sans « e » dans le message d'origine : à confirmer sur le JS de la carte).
4. Le détail dépend de données absentes pour certains territoires (relation nulle, fief sans nation, etc.)
   et renvoie une 404/500 au lieu d'une 403.

## Étapes et suivi

| # | Étape | Statut | Notes |
|---|-------|--------|-------|
| 1 | Cartographier : route(s) `/territoire/{id}`, contrôleur, voter/IsGranted, `security.yaml`, code JS/Twig de la fenêtre de la carte (qui génère le lien) | FAIT 2026-11-06 | voir « Constats de l'étape 1 » ; le contrôleur ne dépend pas du groupe |
| 2 | Obtenir les cas réels : code HTTP exact (403, 404, 500, redirection login) sur un territoire sans groupe | TODO | piste déjà connue : territoire sans groupe + joueur connecté ; un exemple (id + compte) reste utile |
| 3 | Reproduire en local (comptes de test, mot de passe `azerty`) : territoire sans groupe, joueur simple vs scénariste ; écrire un test fonctionnel qui échoue | TODO | prévoir aussi le cas avec groupe (doit continuer à passer) |
| 4 | Règle voulue : un joueur connecté voit la même page qu'avec un groupe, y compris sans groupe | FAIT (décision 2026-11-06) | pas de vue allégée ; vérifier seulement que le template supporte un groupe `null` |
| 4b | Confirmer en prod que 438 a `geojson` NULL et si d'autres territoires sont dans ce cas | TODO | requête SQL lecture seule |
| 5 | Corriger : restaurer la route `POST /world/countries/{territoire}/update` | CODÉ 2026-11-06, NON COMMITÉ | `#[IsGranted('ROLE_TERRITOIRE')]` (cartographe, orga, règle), JSON validé (400 sinon), couleur hex validée, réponse = `addGeoData`. JS : 3 callbacks de succès corrigés (`addSavedGeom`), outils de tracé territoire/région/fief limités à `ROLE_TERRITOIRE` (avant : ORGA ou SCENARISTE, or le scénariste n'a plus l'écriture). Pas de jeton CSRF (le JS n'en envoie pas). Tests `TerritoireZonageTest` + `TerritoireDetailAccesTest` OK, mago OK |
| 5b | Redessiner le zonage de 438 (et des autres sans géométrie) une fois la route rétablie | TODO | action manuelle d'un cartographe |
| 6 | Vérifier la carte : lien de la fenêtre pour plusieurs territoires, plusieurs rôles | TODO | |
| 7 | Mago (format + lint) sur les fichiers touchés, tests, commit sans trailer Claude | TODO | |
| 8 | Merge main puis déploiement prod, prévenir le signaleur | TODO | demander avant de pousser |

## Questions ouvertes

- ~~Que doit voir un utilisateur connecté sans rôle particulier sur la fiche détail d'un territoire ?~~
  **Tranché 2026-11-06 :** exactement ce qu'il voit déjà aujourd'hui sur un territoire avec groupe. La page est en tout
  ou rien (soit elle s'affiche, soit l'accès est refusé), pas de vue allégée à créer. À vérifier à l'étape 1 qu'aucun
  bloc de la page ne suppose un groupe non nul (sinon le protéger).
- Le problème touche-t-il tous les utilisateurs ou seulement certains rôles / certains territoires ?
- Un visiteur non connecté doit-il voir la carte et la fenêtre descriptive ?

## Journal

- 2026-11-06 : plan écrit à partir du message du signaleur, aucune lecture de code encore faite.
- 2026-11-06 : piste principale ajoutée (territoires sans groupe, joueurs connectés uniquement). Toujours aucun code lu.
