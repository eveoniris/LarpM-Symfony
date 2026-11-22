<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\CreaturePuissance;
use App\Enum\Role;
use App\Form\Puissance\CreaturePuissanceType;
use App\Repository\CreaturePuissanceRepository;
use App\Security\MultiRolesExpression;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Échelle de puissance des créatures : lecture pour les scénaristes, écriture pour la cohérence.
 */
#[Route('/echelle-puissance', name: 'puissance.')]
final class PuissanceController extends AbstractController
{
    #[Route(name: 'index')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::COHERENCE))]
    public function indexAction(CreaturePuissanceRepository $repository): Response
    {
        $creatures = $repository->findEchelle();
        $familles = array_values(array_unique(array_filter(array_map(static fn (CreaturePuissance $c): ?string => $c->getFamille(), $creatures))));
        sort($familles);

        return $this->render('puissance/index.twig', [
            'creatures' => $creatures,
            'familles' => $familles,
        ]);
    }

    #[Route('/add', name: 'add')]
    #[IsGranted(new MultiRolesExpression(Role::COHERENCE))]
    public function addAction(Request $request): RedirectResponse|Response
    {
        return $this->handleForm($request, new CreaturePuissance());
    }

    #[Route('/{creature}/update', name: 'update', requirements: ['creature' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::COHERENCE))]
    public function updateAction(Request $request, #[MapEntity] CreaturePuissance $creature): RedirectResponse|Response
    {
        return $this->handleForm($request, $creature);
    }

    #[Route('/{creature}/delete', name: 'delete', requirements: ['creature' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::COHERENCE))]
    public function deleteAction(#[MapEntity] CreaturePuissance $creature): RedirectResponse|Response
    {
        return $this->genericDelete($creature, 'Supprimer une créature', 'La créature a été supprimée', 'puissance.index', [
            ['route' => $this->generateUrl('puissance.index'), 'name' => 'Échelle de puissance'],
            ['name' => 'Supprimer une créature'],
        ]);
    }

    private function handleForm(Request $request, CreaturePuissance $creature): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            request: $request,
            entity: $creature,
            formClass: CreaturePuissanceType::class,
            routes: [
                'root' => 'puissance.',
                'list' => 'puissance.index',
                'detail' => 'puissance.index',
            ],
            msg: [
                'entity' => 'créature',
                'entity_added' => 'La créature a été ajoutée',
                'entity_updated' => 'La créature a été mise à jour',
                'entity_deleted' => 'La créature a été supprimée',
                'entity_list' => 'Échelle de puissance',
                'title_add' => 'Ajouter une créature',
                'title_update' => 'Modifier une créature',
            ],
        );
    }
}
