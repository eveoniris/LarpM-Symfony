# Interface bêta et guide de style : note de reprise

Document de passation pour poursuivre le chantier dans une nouvelle conversation. État au guide v0.27.

## Objectif

Moderniser l'interface (thème sombre « charbon et cuivre », compatible web et mobile, déclinable en application) sans casser l'existant. Le nouveau design est proposé en **bêta**, activable par compte, et décrit par la page `/design`.

## Mécanisme de la bêta

- Champ `user.ui_theme` (jetons `beta` et `light`, séparés par une espace, ou nul), migration `Version20261018120000`. Interrupteur « Interface bêta » dans `/user/{id}/edit`, ouvert à tous les comptes (depuis le 2026-10-22), sur son propre profil seulement.
- `templates/base.html.twig` : `data-ui="beta"` sur `<html>` et chargement de `lm-design.css` pour ces comptes seulement.
- `assets/styles/lm-design.css` : jetons (`--lm-*`), composants (`lm-*`) et habillage des pages existantes sous `html[data-ui="beta"]`.
- Les gabarits modifiés doivent rendre un HTML inchangé hors bêta (classes conditionnées par `app.user.uiBeta`).
- `/design` (`IndexController::designAction`) : toujours réservé aux rôles admin, scénariste, orga, règles et stock.

## Fichiers de référence

| Élément | Emplacement |
|---|---|
| Jetons, composants, habillage bêta | `assets/styles/lm-design.css` |
| Guide de style (page) | `templates/index/design.twig` (généré, voir ci-dessous) |
| Modèle de callout | `templates/_partials/callout.twig` (généré, non encore utilisé) |
| Feuille d'impression commune | `assets/styles/lm-print.css`, `templates/base.print.twig` |
| Plans de chantier | `docs/plans/` |

## Outils locaux (non versionnés)

Dossier `.local/design-tools/` (ignoré par git via `/.local/`, hors de `var/` et de `public/assets`, donc non purgé par les nettoyages de cache).

- `design-body.html` : **source** du guide (contenu HTML des sections).
- `build.ps1` : génère le guide autonome (`guide-de-style.html`, publié comme Artifact), la page `templates/index/design.twig` et `templates/_partials/callout.twig`, avec le sommaire. Les identifiants de section sont créés à partir des titres `<h2>N. …</h2>`.
- `add-v0NN.ps1`, `apply-qa.ps1`, `neutral-tone.ps1` : scripts d'édition ponctuels, conservés pour mémoire.
- `_old/` : anciennes copies.

Procédure d'une mise à jour du guide :

1. Modifier `design-body.html` (et `assets/styles/lm-design.css` si besoin).
2. Incrémenter le numéro de version (en-tête, « Version actuelle », ligne d'historique de la section 11).
3. `pwsh -NoProfile -File .local/design-tools/build.ps1`.
4. Vérifier : `docker exec larpm-symfony-webserver-1 php bin/console lint:twig templates/index/design.twig`.
5. Si le CSS a changé : `docker exec larpm-symfony-webserver-1 php bin/console asset-map:compile` (plusieurs minutes).
6. Committer `templates/index/design.twig` (et le CSS). Ne pas committer `.local/`.

Si `design-body.html` est perdu, il se reconstruit depuis `templates/index/design.twig` (contenu entre `{% verbatim %}` et `{% endverbatim %}`, sans le sommaire généré ni le bouton de bascule du guide autonome).

## Conventions

- **Couleurs par rôle** : secret = or (espion), scénarisation = violet (plume), organisation = bleu (presse-papiers), administratif = bordeaux (clé), jeu de domaine = pourpre (château, couronne), PNJ = vert olive. États : information = cyan, succès = vert, attention = ambre, erreur = rouge. Étiquettes « objet bleu » et « objet orange » : couleurs figées, hors thème.
- **Affinité de compétence** : favorite (étoile, vert), offerte à la création (cadeau, bleu ciel), méconnue (point d'interrogation, ambre), remises en vert.
- **Listes** : actions à droite dans une colonne de largeur fixe ; 3 actions ou moins visibles, au-delà un menu « Actions » ; pagination avec résumé et choix du nombre de lignes ; « Lire la suite » pour les textes longs ; « Afficher plus » (paquets de 25) dans les onglets.
- **Lisibilité sur la photo de fond** : pas de voile global ; panneaux en verre dépoli derrière l'en-tête, les onglets et le pied de page.
- **Rédaction** : français avec accents, ton neutre et factuel (pas de « je »). Messages de commit sans tiret long « — » (préférer virgule, tiret simple ou parenthèses), avec la ligne `Co-Authored-By`.
- Ne rien pousser, fusionner ni déployer en production sans demande explicite.

## Environnement de développement

- Conteneur `larpm-symfony-webserver-1` (php, console, phpunit).
- Site local en HTTP : `http://localhost` (ou `http://larpmanager.test`). D'anciens cookies HTTPS peuvent empêcher la connexion sur `larpmanager.test` : utiliser `localhost` ou effacer les données du site.
- Les assets sont servis depuis `public/assets` (AssetMapper compilé) : recompiler après toute modification CSS ou JS.
- En production : `doctrine:migrations:migrate`, `importmap:install`, `asset-map:compile` et `cache:clear --env=prod`.

## Chantiers réalisés

Habillage bêta (navigation, menus, panneau « Actions », onglets, listes, formulaires, badges, encadrés), page `/design` et guide, détail des groupes transverses en onglets (444 requêtes ramenées à 14-19), Who's who imprimable, en-têtes de `/stats/list` par rôle, repasse des listes (pagination, filtres, actions, « Afficher plus »), cadres de rôle explicites (`data-role`) sur les blocs réservés et actions de `/gn/{id}/groupes` en boutons (suivi : `docs/plans/cadres-roles.md`), polices auto-hébergées (Cinzel, Source Sans 3, JetBrains Mono), panneau de bois en WebP en variante.

## Chantiers faits depuis la note initiale (guide v0.20 à v0.27)

Territoires (liste et fiche : cadres secrets, hiérarchie, ancres, « Lire la suite »), `/competence` et `/classe` (grille, onglets Classes et Affinités, matrice colorée ; suivi `docs/plans/competence-classe.md`), photo en grand en modale, mode sombre ou clair mémorisé dans `user.ui_theme`, boutons de la fiche GN, corrections mobile à 375 px, comparaison HTML hors bêta (voir `docs/plans/cadres-roles.md`), contrôle mobile à 375 px (fiche personnage, formulaires, onglets), trombinoscope de la fiche personnage en modale (les autres photos ne sont pas des liens).

Factorisation JavaScript : `assets/lm-ui.js` (« Lire la suite », copie des e-mails, modale photo) et `assets/lm-lists.js` (tableaux de liste bêta : actions de ligne, cartes mobiles, « Afficher plus »), tous deux importés par `assets/app.js`. Plus aucun script en ligne dans les gabarits concernés.

## Décisions prises

- Bloc E-mails des groupes transverses : l'onglet E-mails seul est conservé.
- Doublon de type « Confrérie » (id 2, sans groupe) : supprimé de la base locale et purgé en production par le mainteneur.
- Messages de commit sans ligne `Co-Authored-By`.

## Ouverture de la bêta à tous (2026-10-22)

L'interrupteur est visible pour tout compte connecté. Conséquence : les pages vues par les joueurs ordinaires (fiche personnage, inscriptions, groupes, messagerie) passent en bêta pour ceux qui l'activent ; les pages réservées aux rôles de gestion ont été revues en priorité, celles des joueurs ont reçu les contrôles à 375 px décrits plus haut. Un retour en arrière individuel reste possible depuis le profil.

## Chantiers restants

- Confirmer le rendu mobile sur un appareil réel (les contrôles à 375 px ont été faits sous Chromium en émulation).
- Découper `lm-design.css` (environ 750 lignes) par domaine, partiel Twig commun pour les en-têtes de carte à icône de rôle.
- Mise en production sur demande : push de `main`, fusion dans `prod`, puis migration doctrine (aucune nouvelle migration dans ce chantier), `importmap:install`, `asset-map:compile`, `cache:clear --env=prod`.
