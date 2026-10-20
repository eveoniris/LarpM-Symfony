# Repasse générale des listes (interface bêta)

Objectif : appliquer les règles de liste du guide `/design` (§5, §5b, §4f) aux pages de liste, **en interface bêta uniquement**.
Sans bêta (`app.user.uiBeta` faux), le HTML rendu reste celui d'avant (sauf les URL générées pour les groupes transverses, voir décisions).

## Phases

- [x] 0. Inventaire des listes et captures de référence
- [x] 1. Pagination (partiel + contrôleurs / limite)
- [x] 2. Barre de filtres
- [x] 3. Colonne d'actions et cartes mobiles
- [x] 4. Liste `/groupeSecondaire` (lire la suite, badge de type, alias de route)
- [x] 5. « Afficher plus » dans les onglets de groupe
- [x] 6. Tests, guide `/design` v0.17, rapport final

## Inventaire

| Élément | Emplacement | Usage |
| --- | --- | --- |
| Pagination | `templates/_partials/pagination.html.twig` | 65 gabarits (59 l'incluent en haut et en bas, 6 une seule fois) |
| Filtres de recherche | `templates/_partials/formListSearch.twig` | environ 42 gabarits (recherche + champ « type » + boutons) |
| En-têtes triables | `thead.twig`, `orderBy.twig` | tableaux de liste |
| Boutons d'en-tête | `btnGroup.twig` | `formListSearch` et `groupeSecondaire/list.twig` |
| Actions de ligne | écrites dans chaque gabarit (`btn-toolbar`, `btn-group`) | traitées de façon générique par `_partials/lmLists.twig` |

Pages de référence : `/groupeTransverse` (alias `/groupeSecondaire`), `/user/list`, `/personnage/list`, `/territoire/fief`.

Lecture de la limite par page : trois mécanismes coexistent.

- `PagerService::getLimit` / `setLimit` (groupes transverses, listes via `searchPaginated`) : le paramètre `limit` de l'URL était ignoré (la valeur lue était écrasée par la valeur par défaut). Il est désormais pris en compte pour 25, 50 ou 100.
- `getRequestLimit()` du contrôleur de base (`/user/list`, défaut 10) : passe par `PagerService::getLimit`, donc même correction.
- Contrôleurs qui lisent `limit` eux-mêmes (`personnage`, `territoire`, `lignee`, `background`, défaut 50) : déjà fonctionnels.

## Réalisation

- **Pagination bêta** : résumé « 1 à 25 sur N », choix 25 / 50 / 100 (liens, conservés dans l'URL, retour page 1), « Précédent » / « Suivant » avec libellé, fenêtre de pages autour de la page courante (cuivre, `aria-current="page"`), champ « Aller à la page » au-delà de 10 pages, cibles de 44 px. Le pagineur du haut est masqué (CSS `:has`) quand la page affiche 25 lignes ou moins ; il est numéroté par la fonction Twig `lm_pager_slot()` (`src/Twig/ListExtension.php`).
- **Filtres bêta** : recherche toujours visible, panneau « Filtres (n) » replié contenant le champ « rechercher dans », puces des filtres actifs, « Réinitialiser » en bouton texte, boutons d'en-tête (Ajouter, Imprimer, …) en `lm-btn`.
- **Actions et cartes** : script `templates/_partials/lmLists.twig` (chargé par `base.html.twig` pour la bêta seulement) : tableaux avec en-tête et sans cellules fusionnées passés en `lm-table lm-table--cards` avec `data-label`; colonne « Actions » de largeur fixe (13 rem) ; 3 actions ou moins visibles, 4 ou plus : première action non destructive + menu « Actions », action destructive en rouge séparée ; sous 640 px, menu « ⋯ » regroupant toutes les actions.
- **Groupes transverses** : description repliée (« Lire la suite », `clamp.twig`), type en badge neutre avec icône, URL canonique `/groupeTransverse`, `/groupeSecondaire` conservé en alias (`groupeSecondaire.list.alias`), noms de routes `groupeSecondaire.*` inchangés.
- **Afficher plus** : onglets Membres et Postulants au-delà de 25 lignes : paquets de 25, recherche côté client (sans normalisation d'accents ni requête).

## Décisions

- Les actions de ligne sont traitées par script côté client plutôt que gabarit par gabarit : 65 listes écrivent leurs actions à la main. Sans JavaScript, la liste reste utilisable (boutons Bootstrap d'origine).
- `/user/list` garde sa limite par défaut de 10 lignes (changer le défaut modifierait aussi le rendu hors bêta).
- Le filtre « Filtrer par rôle » de `/user/list` reste un formulaire séparé (non déplacé dans le panneau).
- `/groupeSecondaire` et `/groupeTransverse` sont servis par la même action ; les liens générés (`path('groupeSecondaire.list')`) pointent maintenant vers `/groupeTransverse`, y compris hors bêta (le contenu des pages reste identique).
- Les tableaux contenant `colspan`/`rowspan` ou placés dans un `<form>` ne sont pas transformés.

## Tests

- `tests/Functional/GroupeSecondaire/GroupeSecondaireListTest.php` : URL canonique et alias, badge de type avec icône, résumé « 1 à 25 sur 30 », limite 50 conservée, rendu hors bêta inchangé.
- `GroupeSecondaireDetailTabsTest` : « Afficher plus » présent en bêta au-delà de 25 membres (31 lignes), absent hors bêta.
- Suites exécutées dans le conteneur : `GroupeSecondaire`, `User`, `Personnage`, `Territoire`, `Groupe`, `Security` (175 tests, tous réussis avant l'ajout des deux derniers tests ; `GroupeSecondaire` : 26 tests réussis après).

## Mesures

- Contrôle visuel (compte de test en bêta) : `/groupeTransverse`, `/user/list`, `/personnage/list` en desktop ; `/groupeTransverse` et `/personnage/list` en 375 px (cartes, menu ⋯) ; menu « Actions » de `/user/list` ouvert ; onglet Membres du groupe 1 (87 membres) : 25 affichés, +25 par clic, recherche côté client (0 requête supplémentaire).
- Pas de captures d'écran archivées (phase 0) : l'état d'origine est celui du dépôt avant ce commit (hors bêta, le rendu est inchangé et couvert par un test).
- Limites connues : le menu des actions est construit par script (sans JavaScript, les boutons d'origine restent) ; les listes dont les actions sont des icônes sans texte gardent des boutons icône (libellé dans `title` et `aria-label`) ; la cible visuelle des tableaux avec `colspan` (non transformés) reste à traiter liste par liste.
- En développement, `public/assets` a été vidé pour servir les sources sans recompilation ; `asset-map:compile` reste à lancer au déploiement.
