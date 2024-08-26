<?php

namespace App\Service;

use App\Entity\Personnage;
use App\Form\PersonnageFindForm;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PersonnageService
{
    public array $columnDefinitions = [
        'colId' => ['label' => '#', 'fieldName' => 'id',  'sortFieldName' => 'id', 'tooltip' => 'Numéro d\'identifiant'],
        'colStatut' => ['label' => 'S', 'fieldName' => 'status',  'sortFieldName' => 'status', 'tooltip' => 'Statut'],
        'colNom' => ['label' => 'Nom', 'fieldName' => 'nom',  'sortFieldName' => 'nom', 'tooltip' => 'Nom et surnom du personnage'],
        'colClasse' => ['label' => 'Classe', 'fieldName' => 'classe',  'sortFieldName' => 'classe', 'tooltip' => 'Classe du personnage'],
        'colGroupe' => ['label' => 'Groupe', 'fieldName' => 'groupe',  'sortFieldName' => 'groupe', 'tooltip' => 'Dernier GN - Groupe participant'],
        'colRenommee' => ['label' => 'Renommée', 'fieldName' => 'renomme',  'sortFieldName' => 'renomme', 'tooltip' => 'Points de renommée'],
        'colPugilat' => ['label' => 'Pugilat', 'fieldName' => 'pugilat',  'sortFieldName' => 'pugilat', 'tooltip' => 'Points de pugilat'],
        'colHeroisme' => ['label' => 'Héroïsme', 'fieldName' => 'heroisme',  'sortFieldName' => 'heroisme', 'tooltip' => 'Points d\'héroisme'],
        'colUser' => ['label' => 'Utilisateur', 'fieldName' => 'user',  'sortFieldName' => 'user', 'tooltip' => 'Liste des utilisateurs (Nom et prénom) par GN'],
        'colXp' => ['label' => 'Points d\'expérience', 'fieldName' => 'xp',  'sortFieldName' => 'xp', 'tooltip' => 'Points d\'expérience actuels sur le total max possible'],
        'colHasAnomalie' => ['label' => 'Ano.', 'fieldName' => 'hasAnomalie',  'sortFieldName' => 'hasAnomalie', 'tooltip' => 'Une pastille orange indique une anomalie'],
    ];

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ValidatorInterface $validator,
        protected readonly FormFactoryInterface $formFactory,
        protected readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * Retourne le tableau de paramètres à utiliser pour l'affichage de la recherche des personnages.
     */
    public function getSearchViewParameters(
        Request $request,
        string $routeName,
        array $routeParams = [],
        array $columnKeys = [],
        array $additionalViewParams = [],
        ?Collection $sourcePersonnages = null,
        ?QueryBuilder $query = null,
    ): array {
        // récupère les filtres et tris de recherche + pagination renseignés dans le formulaire
        $orderBy = $request->get('order_by') ?: 'id';
        $orderDir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $isAsc = 'ASC' === $orderDir;
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $criteria = [];
        $alias = $query->getRootAliases()[0] ?? 'p';

        $formData = $request->query->get('personnageFind');
        $religion = isset($formData['religion']) ? $this->entityManager->find(
            'LarpManager\Entities\Religion',
            $formData['religion']
        ) : null;
        $competence = isset($formData['competence']) ? $this->entityManager->find(
            'LarpManager\Entities\Competence',
            $formData['competence']
        ) : null;
        $classe = isset($formData['classe']) ? $this->entityManager->find(
            'LarpManager\Entities\Classe',
            $formData['classe']
        ) : null;
        $groupe = isset($formData['groupe']) ? $this->entityManager->find(
            'LarpManager\Entities\Groupe',
            $formData['groupe']
        ) : null;
        $optionalParameters = '';

        // construit le formulaire contenant les filtres de recherche

        $form = $this->formFactory->create(
            PersonnageFindForm::class,
            null,
            [
                'data' => [
                    'religion' => $religion,
                    'classe' => $classe,
                    'competence' => $competence,
                    'groupe' => $groupe,
                ],
                'method' => 'get',
                'csrf_protection' => false,
            ]
        );

        $form->handleRequest($request);

        // récupère les nouveaux filtres de recherche
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['value'];
            $criteria[$type] = $value;
        }
        if ($religion) {
            $criteria['religion'] = $religion->getId();
            $optionalParameters .= "&personnageFind[religion]={$religion->getId()}";
        }
        if ($competence) {
            $criteria['competence'] = $competence->getId();
            $optionalParameters .= "&personnageFind[competence]={$competence->getId()}";
        }
        if ($classe) {
            $criteria['classe'] = $classe->getId();
            $optionalParameters .= "&personnageFind[classe]={$classe->getId()}";
        }
        if ($groupe) {
            $criteria['groupe'] = $groupe->getId();
            $optionalParameters .= "&personnageFind[groupe]={$groupe->getId()}";
        }

        // récupère la liste des personnages, filtrée, ordonnée et paginée
        // 2 choix possibles :
        // - on a déjà fourni la valeur de sourcePersonnages, dans ce cas, on effectue les filtre/tri dessus direct
        // - sourcePersonnages est vide, dans ce cas, on effectue la recherche en base, via le repository
        $repo = $this->entityManager->getRepository(Personnage::class);
        if (null === $sourcePersonnages) {
            // attention, il y a des propriétés sur lesquelles on ne peut pas appliquer le order by
            // car elles ne sont pas en base mais calculées, ça compliquerait trop le sql
            $orderByCalculatedFields = new ArrayCollection(['pugilat', 'heroisme', 'user', 'hasAnomalie', 'status']);
            if ($orderByCalculatedFields->contains($orderBy)) {
                // recherche basée uniquement sur les filtres
                $filteredPersonnages = $repo->findList($criteria);
                // pour le nombre de résultats, pas besoin de refaire de requête, on l'a déjà
                $numResults = count($filteredPersonnages);
                // on applique le tri
                // TODO PersonnageSorter::sort($filteredPersonnages, $orderBy, $isAsc); voir PersonnageManager::sort()
                $personnagesCollection = new ArrayCollection($filteredPersonnages);
                // on découpe suivant la pagination demandée
                $personnages = $personnagesCollection->slice($offset, $limit);
            } else {
                // recherche et applique directement en sql filtres + tri + pagination
                $personnages = $repo->findList(
                    $criteria,
                    ['by' => $orderBy, 'dir' => $orderDir],
                    $limit,
                    $offset
                );
                // refait une requete pour récupérer le nombre de résultats suivant les critères
                $numResults = $repo->findCount($criteria);
            }
        } elseif ($query) {
            $personnages = $query->orderBy($alias.'.'.$orderBy, $orderDir)->getQuery();
        } else {
            // on effectue d'abord le filtre
            // TODO: pour le moment, finalement il n'y a plus besoin de filtre car le composant n'est plus affiché
            // il faudra cependant le mettre en place si un jour on souhaite l'activer hors de la page d'admin de liste perso
            // l'idée ce serait d'utiliser la méthode filter sur la collection et pour chaque champ, de réappliquer la bonne règle
            $filteredPersonnages = $sourcePersonnages;
            $numResults = $filteredPersonnages->count();
            $filteredPersonnagesArray = $filteredPersonnages->toArray();
            // puis le tri
            if ($orderBy && !empty($orderBy)) {
                // TODO PersonnageSorter::sort($filteredPersonnagesArray, $orderBy, $isAsc);
            }
            // puis la pagination
            $personnagesCollection = new ArrayCollection($filteredPersonnagesArray);
            // on découpe suivant la pagination demandée
            $personnages = ($offset && $limit)
                ? $personnagesCollection->slice($offset, $limit)
                : $personnagesCollection->toArray();
        }

        $paginator = $repo->findPaginatedQuery(
            $personnages,
            $limit,  // $this->getRequestLimit(),
            $page // $this->getRequestPage()
        );

        // récupère les colonnes à afficher
        if (empty($columnKeys)) {
            // on prend l'ordre par défaut
            $columnDefinitions = $this->columnDefinitions;
        } else {
            // on reconstruit le tableau dans l'ordre demandé
            $columnDefinitions = [];
            foreach ($columnKeys as $columnKey) {
                if (array_key_exists($columnKey, $this->columnDefinitions)) {
                    $columnDefinitions[] = $this->columnDefinitions[$columnKey];
                }
            }
        }

        return array_merge($additionalViewParams, [
            'personnages' => $personnages,
            'paginator' => $paginator,
            'form' => $form->createView(),
            'optionalParameters' => $optionalParameters,
            'columnDefinitions' => $columnDefinitions,
            'formPath' => $routeName,
            'formParams' => $routeParams,
        ]
        );
    }
}
