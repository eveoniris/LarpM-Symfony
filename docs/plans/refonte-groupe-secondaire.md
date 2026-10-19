# Refonte du détail des groupes transverses

Page concernée : `/groupeSecondaire/{id}/detail` (alias `/groupeTransverse/{id}/detail`), route `groupeSecondaire.detail`.
Objectif : pages à onglets (une route par onglet) et réduction du nombre de requêtes SQL.

## Phases

- [x] 0. Mesures initiales
- [x] 1. Méthodes de repository avec jointures, suppression des boucles redondantes
- [x] 2. Routes par onglet, navigation desktop et mobile, ancienne URL = onglet par défaut
- [x] 3. Gabarits par onglet (Présentation, Membres, Postulants, Scénarisation, E-mails)
- [x] 4. Menus d'actions membres et postulants
- [x] 5. Tests fonctionnels et mesures après
- [x] 6. Guide `/design` (ligne « Groupes transverses » passée à Fait), rapport final

## Mesures

Méthode : script PHP (`KernelBrowser`, env `test`, profiler activé) sur une copie de la base de développement
(`larpm_measure_test`), compte `test@test.com`, groupe 1 (87 membres, 17 postulants).

| Page | Avant | Après |
| --- | --- | --- |
| `/groupeSecondaire/1/detail` (ancienne page unique, puis onglet par défaut) | 444 requêtes SQL (~360 ms à chaud) | 19 |
| onglet Présentation | (inclus dans la page unique) | 19 |
| onglet Membres (87 membres) | (inclus dans la page unique) | 14 (divisé par plus de 30) |
| onglet Postulants (17 postulants) | (inclus dans la page unique) | 15 |
| onglet Scénarisation | (inclus dans la page unique) | 14 |
| onglet E-mails | (inclus dans la page unique) | 14 |

Sur les tests (profiler, jeu de données généré) : le nombre de requêtes de l'onglet Membres ne dépend pas du nombre de membres
(test `testMembersTabQueryCountDoesNotGrowWithMemberCount`) et chaque onglet reste sous 60 requêtes.

## Réalisation

- Repository (`SecondaryGroupRepository`) : `findWithMembres` (membres, personnage, joueur, religions en option, collection du groupe
  initialisée), `findPostulants`, `findInscriptions` (inscrits au GN actif et groupe de jeu, remplace `participeTo` / `getActifs` /
  `identityByLabel` appelés par membre), `findIntrigues`, `countMembres`, `countPostulants`, `hasMembre`, `hasPostulant`.
- Contrôleur : `detailAction` accepte `{tab}` ; seules les données de l'onglet affiché sont chargées ; les e-mails sont calculés une
  fois (deux listes : tous les membres, membres inscrits au GN).
- Gabarits : `detail.twig` (en-tête, onglets desktop `nav-tabs` / `lm-tabs`, offcanvas mobile), `tab/*.twig`, `fragment/membre.twig`,
  `fragment/postulant.twig`, `fragment/clamp.twig` (texte replié, 68 caractères), `fragment/_ui.twig` (classes selon la bêta).
  Anciens fragments `postulants.twig` et `membres.twig` supprimés.
- CSS : bloc `.gs-*` en fin de `assets/styles/style.css` (largeur de lecture, repli, avatar, e-mails), valable avec ou sans bêta.
- Tests : `tests/Functional/GroupeSecondaire/GroupeSecondaireDetailTabsTest.php` (20 tests : accès par rôle, groupe secret, groupe
  anonyme, alias, onglet inconnu, bêta, budget de requêtes). Suite complète : 567 tests verts.

## Décisions

- Branche : travail sur `main` (choix de la personne, pas de nouvelle branche).
- Onglets : `presentation` (défaut), `membres`, `postulants`, `scenarisation`, `emails`.
  Route `groupeSecondaire.detail.tab` (alias `groupeTransverse.detail.tab`), `groupeSecondaire.detail` reste l'onglet par défaut.
- Règles d'accès inchangées : `hasAccess`, groupe secret + `IS_MEMBRE`, groupe anonyme (seul le chef voit les personnages),
  postulants réservés à `canManage` ou `canReadSecret`, scénarisation réservée à `isAdmin`, e-mails réservés à `canManage`.
- Un onglet non autorisé redirige vers l'onglet par défaut avec un message.
- Écarts d'interface assumés : « Contacter le chef » masqué pour le chef lui-même ; menu ⋯ non proposé sur sa propre ligne ;
  les emplacements « Documents » et « Objets » (« à venir », sans fonction) de l'ancien bloc Scénarisation ne sont pas repris ;
  « Inscrit » compte tout participant du GN actif avec billet pour le personnage.
- Les feuilles de style `lm-*` ne sont chargées que pour la bêta : les gabarits choisissent leurs classes selon `app.user.uiBeta`
  (Bootstrap sinon), sans CSS dupliqué.
