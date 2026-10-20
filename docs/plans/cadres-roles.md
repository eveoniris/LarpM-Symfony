# Cadres de rôle et liens d'action (interface bêta)

Suivi du chantier « blocs réservés » (guide v0.19). Principe : chaque bloc réservé porte un `data-role` explicite dans le gabarit (sans effet visuel hors bêta) ; `assets/styles/lm-design.css` l'habille sous `html[data-ui="beta"]` (liseré gauche de 4 px, fond teinté, titre en petites capitales avec icône).

## Inventaire

| Fichier | Bloc | Rôle | État |
|---|---|---|---|
| `gn/detail.twig` | Administratif | admin | fait |
| `gn/detail.twig` | Jeu de domaine (icône tour de château en bêta, bouclier sinon) | domaine | fait |
| `gn/list.twig` | Bloc scénarisation (via `cardNotif`) | gm | déjà habillé |
| `gn/groupes.twig` | Actions scénarisation (via `cardNotif`) | gm | déjà habillé, liens convertis |
| `gn/billetterie.twig`, `gn/fiche_retour_import.twig` | Information / Attention | info, warn | fait |
| `groupe/fragment/detail.twig` | Intrigues | gm | fait |
| `groupe/fragment/detail.twig` | Description aux membres (icône espion) | secret | fait |
| `personnage/fragment/admin_resume.twig` | Informations scénariste | gm | fait |
| `objet/detail.twig` | Description scénariste | gm | fait |
| `user/detail.twig`, `user/update.twig` | Support, rôles (administratif) | admin | fait |
| `groupe/addTerritoire.twig`, `groupeGn/fiche_retour_edit.twig`, `classe/fragment/competenceFamily.twig` | Attention | warn | fait |
| `message/*`, `stock/*/index.twig`, `stock/index.twig` | Information / Attention | info, warn | fait |
| `autreMonde`, `cosmogonie`, `espece` `detail.twig` | Titre d'élément scénarisation (`border-gamemaster border-bottom`, pas un cadre) | gm | laissé en l'état |
| `territoire/detail.twig` | 5 cartes `card-header border-warning` à icône espion et titre « Secret » (quêtes commerciales, jeu stratégique, statut, bonus d'origine, description secrète) | secret | fait |
| `territoire/detail.twig` | `text-gamemaster` (boutons « Modifier » en plume, « Description scénariste », icône de statut) | gm | laissé en l'état (marques inline, pas des cadres) |
| `groupeSecondaire/*` | déjà en `lm-callout` et `data-role` | gm, secret | déjà fait |
| `statistique/list.twig`, `_partials/cardNotif.twig` | déjà en `data-role` | divers | déjà fait |

## Liens convertis en boutons (bêta seulement)

- `gn/groupes.twig`, bloc scénarisation : 7 liens (ajouter un groupe, 5 impressions, synthèse matériel) en boutons `btn btn-secondary btn-sm` avec icône ; la liste à puces d'origine reste hors bêta.

À examiner (non traités) : liste à puces « Participer ! / Liste des groupes » de `gn/detail.twig` (liens de navigation, pas d'actions voisines d'un bouton).

## Décisions

- `data-role` posé dans tous les cas (comme `cardNotif`), donc le HTML hors bêta ne diffère que par cet attribut ; l'icône du jeu de domaine est la seule modification conditionnée à `app.user.uiBeta`.
- `border-danger` et `border-warning` sont départagés par `data-role`, plus par la couleur Bootstrap. Les règles `:has(.fa-user-ninja)` et `.border-gamemaster` restent pour les gabarits non encore étiquetés.
- CSS : titres posés dans `.header` (et non `.card-header`) pris en charge ; rôle secret avec liseré double.

## Territoires (guide v0.20)

Les cinq en-têtes `border-warning` portent tous l'icône espion et l'infobulle « Secret » : `data-role="secret"` posé sur la carte (liseré double or en bêta). Pour la carte « Description secrète », dont l'en-tête est dans un `.list-group-item`, trois règles CSS ajoutées. Le h6 « Description secrète » interne reste en `border-warning` (sous-titre, pas un cadre).

## Points ouverts

- Territoires : tranché, les en-têtes `border-warning` sont des blocs secrets (icône espion), ni domaine ni attention.
- Autres listes de liens voisins de boutons à relever au fil des pages.
- Vérification visuelle faite sur `/gn/7` et `/gn/7/groupes` (desktop et 375 px) ; `/gn` non modifié (déjà habillé via `cardNotif`).
