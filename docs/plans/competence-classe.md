# Pages /competence et /classe en interface bêta

Suivi du chantier « Compétences, classes » (guide de style §4b, §4c, §5, §5b ; backlog §10). Branche : `claude/ui-competence-classe-32affe`. Guide visé : v0.24.

## Phases

- [x] Lecture du guide (§4b, §4c, §5, §5b) et de `design-beta-reprise.md`
- [x] Inventaire des gabarits et contrôleurs
- [x] Données : `TypeClasse`, `Classe::getType()`, paramètres bêta des contrôleurs
- [x] CSS bêta (`lm-design.css`) : grille de familles, matrice, filtre de type, verre dépoli
- [x] Gabarit `competence/list.twig` (grille par famille, quatre niveaux, coût en XP, « Lire la suite »)
- [x] Gabarit `classe/list.twig` (filtre de type, matrice, affinité du joueur, cartes de classe)
- [x] Détails (`competence/detail.twig`, `classe/detail.twig`) : non retouchés (habillage bêta général suffisant, hors périmètre de la grille et de la matrice)
- [x] Tests fonctionnels (filtre de type, visibilité joueur, HTML hors bêta)
- [x] Vérification visuelle bêta (desktop, 375 px) et hors bêta (HTML anonyme identique avant et après, hors barre de débogage)
- [x] Guide v0.24 (backlog §10, historique §11, build, lint:twig)

## Inventaire

| Élément | Fichier | Remarque |
|---|---|---|
| Liste des compétences | `templates/competence/list.twig`, `CompetenceController::indexAction` | Pagination par 100 ; un joueur ne voit que le niveau Apprenti non secret |
| Détail compétence | `templates/competence/detail.twig` | |
| Liste des classes | `templates/classe/list.twig` + `classe/fragment/competenceFamily*.twig`, `ClasseController::indexAction` | Un joueur ne voit que les classes de création |
| Coût des compétences (admin) | `templates/classe/competences.twig`, route `classe.competences.cout` | Matrice existante, hors périmètre |
| Détail classe | `templates/classe/detail.twig` | |
| Texte long | `groupeSecondaire/fragment/clamp.twig`, `.gs-clamp` (`style.css`) | Script de bascule lié une fois comme dans `territoire/list.twig` |

## Décisions

- **Type de classe** : aucune colonne en base. Le type (combat, social, savoir) est déduit de `label_masculin` par `App\Enum\TypeClasse` (liste du guide §4c) ; toute autre classe est « Autre ». Une colonne dédiée serait plus robuste (point ouvert).
- **Filtre de type** : paramètre GET `?type=combat|social|savoir`, côté serveur, appliqué uniquement en bêta (le HTML hors bêta ne change pas). Il réduit aussi les colonnes de la matrice.
- **Matrice** : lignes = familles, colonnes = classes, première colonne collante, défilement horizontal dans un conteneur. Cases : favorite (étoile, vert), offerte à la création (cadeau, bleu ciel), méconnue (point d'interrogation, ambre) = famille absente des trois listes de la classe. « Normale » reste sans signe (§4b). Une famille à la fois offerte et favorite affiche les deux signes.
- **Affinité du joueur** : classe du personnage actif (`User::getPersonnage()`), colonne et carte mises en évidence, badge « Votre classe ».
- **Coût par niveau** : lu dans `Level` (`cout`, `cout_favori`, `cout_meconu`), commun à toutes les familles. Niveaux d'index 1 à 4 (Grand Maître exclu).
- **Visibilité joueur inchangée** : un joueur voit les coûts des quatre niveaux (règle publique) mais seulement la description Apprenti non secrète (`CompetenceVisibilityTest`). Rien n'est élargi.
- **Pagination en bêta** : limite portée à 500 et pagination masquée, pour ne pas couper une famille entre deux pages.
- **N+1** : les trois collections d'affinité des classes sont chargées par une requête avec jointures.
- **Rôles de couleur** : secret = or + espion ; états inchangés ; verre dépoli (`color-mix` + `backdrop-filter: blur(6px)`) derrière les textes, pas de voile sur la photo.

## Procédure de test (le conteneur monte le dépôt principal, pas le worktree)

Le conteneur `larpm-symfony-webserver-1` monte `F:\dev\LarpM-Symfony` sur `/app`. Pour tester : copier les fichiers modifiés du worktree dans le dépôt principal, tester, puis tout annuler (`git checkout -- <fichiers suivis>` et suppression des fichiers non suivis copiés). Liste des fichiers copiés tenue ci-dessous ; rien n'est commité dans le dépôt principal.

## Points ouverts

- Libellés de classes de production non vérifiés (la base locale est anonymisée : `Classe_m_N`), donc le mapping de type n'est validé que par test.
- Icônes de type de classe : le guide utilise épée, bulle et parchemin (sprite SVG) ; les gabarits utilisent FontAwesome (`fa-hand-fist`, `fa-comments`, `fa-scroll`).

## Résultats de vérification

- Tests : `tests/Functional/Classe/ClasseCompetenceBetaTest.php` (filtre de type, colonnes de la matrice, signes d'affinité, visibilité joueur, « Lire la suite », HTML hors bêta) ; suite `tests/Functional` complète lancée dans le conteneur.
- Visuel (navigateur intégré, compte `test@test.com`) : `/classe` et `/competence` en bêta à ~1024 px et 375 px, sans débordement de page ; la matrice défile dans son cadre (correction : `position: relative` sur le cadre, sinon les libellés masqués absolus étendaient la page).
- Non vérifié visuellement : filtre de type avec de vrais libellés (la base locale ne contient que `Classe_m_N`, donc tout est « Autre »), « Lire la suite » (aucune description longue en base locale), mise en évidence de la classe du joueur (le compte de test n'a pas de personnage actif). Ces trois points sont couverts par les tests fonctionnels pour la logique, pas pour le rendu.
- Test d'une page hors bêta : comparaison du HTML d'une requête anonyme avant et après.
