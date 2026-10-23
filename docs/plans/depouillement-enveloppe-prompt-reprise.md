# Prompt pour reprendre le dépouillement d'enveloppes

À coller tel quel au début d'une conversation neuve (Claude Code ouvert dans `F:\dev\LarpM-Symfony`).

```text
Reprends le chantier « dépouillement d'enveloppes de retour de jeu » de LarpManager (Symfony 8.1, PHP 8.4, Docker).

Avant toute chose :
1. Lis docs/plans/depouillement-enveloppe-reprise.md (état, décisions déjà prises, reste à faire, pièges techniques).
   Le détail complet des décisions et le suivi étape par étape sont dans la mémoire du projet : fiche-retour-groupe-plan.md
   (indexée dans MEMORY.md, lis surtout les sections H, I, J et la ligne de l'étape 11 du tableau de suivi).
2. Vérifie l'état réel, sans me croire sur parole :
   - git branch --show-current : feat/depouillement-enveloppe ;
   - git status : seul public/i.php doit être non suivi (il n'est pas de nous, n'y touche pas) ;
   - git log --oneline origin/main..HEAD : 18 commits de code (le dernier est 7a086f0a) puis un commit de documentation ;
   - la suite de tests : docker compose exec webserver php -d memory_limit=2G ./vendor/bin/phpunit
     (824 tests, tous verts attendus).

Règles de travail :
- Réponds en français, sans tiret cadratin.
- Pas de ligne Co-Authored-By dans les commits.
- Avant chaque commit : mago format puis mago lint sur les fichiers modifiés, et les tests concernés.
- Délègue les recherches dans le dépôt aux sous-agents recherche et grep, et les lancements de tests à l'agent tests ;
  garde ton contexte petit.
- Ne pousse rien sans mon accord explicite.
- Ne redemande pas les décisions déjà prises (elles sont dans le document). Ne me pose une question que si une règle manque vraiment.

Tâche : terminer l'étape 11 (conception d'héritier).
- 11a : enregistrer les conceptions depuis le contenu d'une enveloppe de joueur (service, contrôleur, section dans l'écran,
  résumé du récapitulatif, tests). L'entité Conception, les règles du tirage d20 et la limite d'âge de 45 ans existent déjà.
- 11b : écran du scénariste pour créer le ou les héritiers (ou gérer plus tard, consigné), avec classe secrète et sa garde à la
  reprise, langue des parents, lignée, chronologie de naissance, XP de création. Jamais d'héritier pour une simulation.
Avance par petits commits, et mets à jour le tableau de suivi de la mémoire à chaque étape terminée.

Ensuite : étape 12 (dispatch réel) à cadrer avec moi, puis le merge direct sur main. Demande-moi si on pousse main.
```

## Variante courte (si le contexte est déjà chargé de la mémoire)

```text
Reprends l'étape 11 (héritier) du dépouillement d'enveloppes : lis docs/plans/depouillement-enveloppe-reprise.md, vérifie
git log --oneline origin/main..HEAD (dernier commit de code 7a086f0a) et les tests, puis fais 11a, 11b, par petits commits.
Français sans tiret cadratin, pas de Co-Authored-By, mago avant commit, ne rien pousser sans mon accord.
```
