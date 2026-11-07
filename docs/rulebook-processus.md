# Rulebook : processus de travail

Le rulebook (`/rulebook`, bêta) est la **source de vérité des règles de gameplay**. Il est stocké en base ; le dossier
`docs/rulebook/` n'en est qu'un cache local (non versionné, régénéré par `app:rulebook:exporter`).

## Qui fait quoi

| Rôle | Peut |
| --- | --- |
| `ROLE_SCENARISTE` | Lire la version claire des règles publiées (statut valide, corrigé ou codé en dur) |
| `ROLE_REGLE` | Tout lire (brouillons, bugs, à confirmer), créer, modifier, publier, consulter l'historique |
| `ROLE_ADMIN` | Comme l'équipe règle, plus la restauration d'une ancienne version |

Tant que la fonctionnalité est en test, il faut aussi `ROLE_BETA_TEST`.

## Faire évoluer une règle

L'ordre est toujours : **rulebook, puis test, puis code**.

1. **Rulebook.** Modifier la règle en ligne (version claire pour les scénaristes, détail technique pour l'équipe règle),
   avec un motif. Statut : `TODO` ou `À CONFIRMER` tant que la règle n'est pas tranchée, `VALIDE` quand elle l'est.
2. **Test.** Écrire ou adapter le test et le taguer avec le code de la règle : `#[Group('XP-03')]` (classe ou méthode).
   Renseigner aussi les « tests associés » de la règle.
3. **Code.** Implémenter, puis lancer `app:rulebook:verifier`.
4. **Publication.** Publier la règle (case « Publiée ») quand la version claire est relue. Une règle sans version claire
   ne peut pas être publiée.

Un bug de règle se déclare en passant la règle en `BUGUÉ` (visible de l'équipe règle seulement), pas en la réécrivant.

## Statuts

| Statut | Sens | Visible des scénaristes |
| --- | --- | --- |
| Valide | Règle confirmée et conforme au code | oui, si publiée |
| Corrigé | Bug corrigé, règle conforme | oui, si publiée |
| Codé en dur | Règle appliquée mais non paramétrable | oui, si publiée |
| Bugué | Le code ne respecte pas la règle | non |
| À faire | Règle pas encore implémentée | non |
| À confirmer | Règle à trancher par l'équipe règle | non |

## Revue périodique

Une fois par mois (et avant chaque GN), l'équipe règle passe en revue les règles `TODO`, `À CONFIRMER` et `BUGUÉ` :

```bash
docker compose exec webserver php bin/console app:rulebook:revue
docker compose exec webserver php bin/console app:rulebook:revue --jours=60
```

La commande liste ces règles, les plus anciennes d'abord. `--jours=N` ne garde que celles non modifiées depuis N jours.
Les points ouverts connus (initiative, origine, conditions de service) y apparaissent.

## Contrôle de cohérence avec les tests

```bash
docker compose exec webserver php bin/console app:rulebook:verifier
docker compose exec webserver php bin/console app:rulebook:verifier --strict
```

Signale les règles valides sans test, les références de tests introuvables et les tags `#[Group('XX-NN')]` dont le code
n'existe pas dans le rulebook. `--strict` échoue sur les deux derniers cas (à brancher en CI quand la base de règles y
sera disponible).

## Cache local pour le travail de développement

```bash
docker compose exec webserver php bin/console app:rulebook:exporter
```

Écrit `docs/rulebook/<domaine>.md` et `docs/rulebook/index.md`. Ces fichiers sont écrasés à chaque enregistrement d'une
règle et au déploiement : ne jamais les modifier à la main.
