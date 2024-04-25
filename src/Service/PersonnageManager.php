<?php

namespace App\Service;

use App\Entity\Personnage;
use App\Form\PersonnageFindForm;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class PersonnageManager
{
    public array $columnDefinitions = ['colId', 'colStatut', 'colNom', 'colClasse', 'colGroupe', 'colUser'];
    public function __construct(
        protected readonly ContainerInterface $container,
        protected readonly EntityManagerInterface $entityManager
    )    {
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
        Collection $sourcePersonnages = null): array
    {
        // récupère les filtres et tris de recherche + pagination renseignés dans le formulaire
        $orderBy = $request->get('order_by') ?: 'id';
        $orderDir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $isAsc = 'ASC' == $orderDir;
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $criteria = [];

        $formData = $request->query->get('personnageFind');
        $religion = isset($formData['religion']) ? $this->entityManager->find('LarpManager\Entities\Religion', $formData['religion']) : null;
        $competence = isset($formData['competence']) ? $this->entityManager->find('LarpManager\Entities\Competence', $formData['competence']) : null;
        $classe = isset($formData['classe']) ? $this->entityManager->find('LarpManager\Entities\Classe', $formData['classe']) : null;
        $groupe = isset($formData['groupe']) ? $this->entityManager->find('LarpManager\Entities\Groupe', $formData['groupe']) : null;
        $optionalParameters = '';

        // construit le formulaire contenant les filtres de recherche

        $form = $this->container->get('form.factory')->create(
            new PersonnageFindForm(),
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
        )->getForm();

        $form->handleRequest($request);

        // récupère les nouveaux filtres de recherche
        if ($form->isValid()) {
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
        if (!$sourcePersonnages || empty($sourcePersonnages)) {
            $repo = $this->entityManager->getRepository(Personnage::class);

            // attention, il y a des propriétés sur lesquelles on ne peut pas appliquer le order by
            // car elles ne sont pas en base mais calculées, ça compliquerait trop le sql
            $orderByCalculatedFields = new ArrayCollection(['pugilat', 'heroisme', 'user', 'hasAnomalie', 'status']);
            if ($orderByCalculatedFields->contains($orderBy)) {
                // recherche basée uniquement sur les filtres
                $filteredPersonnages = $repo->findList($criteria);
                // pour le nombre de résultats, pas besoin de refaire de requête, on l'a déjà
                $numResults = count($filteredPersonnages);
                // on applique le tri
                // TODO PersonnageSorter::sort($filteredPersonnages, $orderBy, $isAsc);
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

        $paginator = new Paginator(
            $numResults,
            $limit,
            $page,
            $this->app['url_generator']->generate($routeName, $routeParams).'?page=(:num)&limit='.$limit.'&order_by='.$orderBy.'&order_dir='.$orderDir.$optionalParameters
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
