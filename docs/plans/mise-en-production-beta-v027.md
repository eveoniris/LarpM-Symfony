# Mise en production : interface bêta (guide v0.27)

Préparée le 2026-10-21. Rien n'est poussé ni déployé à la date de rédaction.

## Contenu de la livraison

30 commits locaux sur `main` (73 fichiers, environ +3400 / -675 lignes) : groupes transverses en onglets, repasse des listes, cadres de rôle, territoires, `/competence` et `/classe`, photo en modale, mode sombre ou clair mémorisé, modules JavaScript `assets/lm-ui.js` et `assets/lm-lists.js`, guide `/design` v0.27.

## Contrôles faits avant livraison

- Suite PHPUnit complète : 585 tests, 1198 assertions, verte.
- `mago format --dry-run` : conforme (6 fichiers reformatés, commit `style`).
- `mago lint` et `mago analyze` : encore des alertes au-delà des baselines (complexité de `ClasseController` et `BaseUser`, tests longs, mot « password » détecté dans un test). Les deux étapes sont informatives dans la CI (`continue-on-error`), le format est propre.
- `php-cs-fixer` : 10 fichiers à corriger sur 908, informatif (`continue-on-error`), dette existante.
- Simulation de la fusion `main` vers `prod` (`git merge-tree`) : aucun conflit. `prod` contient 7 commits propres (correctifs RGPD, routes, secrets) absents de `main`, conservés par la fusion.
- Migrations : aucune nouvelle. La migration `Version20261018120000` (colonne `ui_theme`) est déjà dans `origin/prod`.
- Dépendances : `composer.json`, `composer.lock`, `importmap.php` inchangés.

## Étapes

1. `git push origin main` : déclenche la CI (PHPUnit, mago, cs). Attendre PHPUnit vert.
2. `git checkout prod && git merge main`, puis `git push origin prod` : la CI tourne sur `prod`, puis le workflow « Deploy to Production » se lance seul (`workflow_run`).
3. Le déploiement fait : `git reset --hard origin/prod`, `composer install`, `doctrine:migrations:migrate` (rien à appliquer pour cette livraison, sauf si `ui_theme` n'a jamais été migrée en production), `cache:clear` et `cache:warmup`, `importmap:install`, `asset-map:compile`. Aucune étape manuelle sur le serveur.
4. Revenir sur `main` : `git checkout main`.

## Vérifications après déploiement

- Connexion avec un compte de rôle bêta, activation de l'interface bêta dans `/user/{id}/edit`.
- `/groupeTransverse` et un détail de groupe (onglets), `/territoire`, `/competence`, `/classe` (onglet Affinités), `/design`.
- Bouton sombre/clair : le choix doit rester après rechargement.
- Compte sans bêta : une page de liste (par exemple `/user/list`) inchangée.
- Contrôler en production le type de classe (enum `TypeClasse`, 17 libellés) : filtre Combat 6, Social 6, Savoir 5.

## Retour arrière

`git revert -m 1 <fusion>` sur `prod` puis push, ou remise de `prod` sur le commit `7b876566` (état avant livraison) si le déploiement échoue. Aucune migration à défaire.
