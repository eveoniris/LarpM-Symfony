<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FriseAge;
use App\Entity\FriseEntree;
use App\Enum\Role;
use App\Form\Frise\FriseAgeType;
use App\Form\Frise\FriseEntreeType;
use App\Repository\FriseAgeRepository;
use App\Security\MultiRolesExpression;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Chronologie du monde : lecture pour les scénaristes, écriture pour la cohérence.
 */
#[Route('/chronologie-monde', name: 'frise.')]
final class FriseController extends AbstractController
{
    #[Route(name: 'index')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::COHERENCE))]
    public function indexAction(FriseAgeRepository $repository): Response
    {
        return $this->render('frise/index.twig', [
            'ages' => $repository->findFrise(),
        ]);
    }

    #[Route('/age/add', name: 'age.add')]
    #[IsGranted(new MultiRolesExpression(Role::COHERENCE))]
    public function addAgeAction(Request $request): RedirectResponse|Response
    {
        return $this->handleForm($request, new FriseAge(), FriseAgeType::class, 'âge');
    }

    #[Route('/age/{age}/update', name: 'age.update', requirements: ['age' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::COHERENCE))]
    public function updateAgeAction(Request $request, #[MapEntity] FriseAge $age): RedirectResponse|Response
    {
        return $this->handleForm($request, $age, FriseAgeType::class, 'âge');
    }

    #[Route('/age/{age}/delete', name: 'age.delete', requirements: ['age' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::COHERENCE))]
    public function deleteAgeAction(#[MapEntity] FriseAge $age): RedirectResponse|Response
    {
        return $this->genericDelete($age, 'Supprimer un âge', "L'âge et ses entrées ont été supprimés", 'frise.index', [
            ['route' => $this->generateUrl('frise.index'), 'name' => 'Chronologie du monde'],
            ['name' => 'Supprimer un âge'],
        ]);
    }

    #[Route('/entree/add', name: 'entree.add')]
    #[IsGranted(new MultiRolesExpression(Role::COHERENCE))]
    public function addEntreeAction(Request $request, FriseAgeRepository $ageRepository): RedirectResponse|Response
    {
        $entree = new FriseEntree();
        $ageId = $request->query->getInt('age');
        if ($ageId > 0 && ($age = $ageRepository->find($ageId)) instanceof FriseAge) {
            $entree->setAge($age);
        }

        return $this->handleForm($request, $entree, FriseEntreeType::class, 'entrée');
    }

    #[Route('/entree/{entree}/update', name: 'entree.update', requirements: ['entree' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::COHERENCE))]
    public function updateEntreeAction(Request $request, #[MapEntity] FriseEntree $entree): RedirectResponse|Response
    {
        return $this->handleForm($request, $entree, FriseEntreeType::class, 'entrée');
    }

    #[Route('/entree/{entree}/delete', name: 'entree.delete', requirements: ['entree' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::COHERENCE))]
    public function deleteEntreeAction(#[MapEntity] FriseEntree $entree): RedirectResponse|Response
    {
        return $this->genericDelete($entree, 'Supprimer une entrée', "L'entrée a été supprimée", 'frise.index', [
            ['route' => $this->generateUrl('frise.index'), 'name' => 'Chronologie du monde'],
            ['name' => 'Supprimer une entrée'],
        ]);
    }

    private function handleForm(Request $request, object $entity, string $formClass, string $nom): RedirectResponse|Response
    {
        $isAge = $entity instanceof FriseAge;

        return $this->handleCreateOrUpdate(
            request: $request,
            entity: $entity,
            formClass: $formClass,
            routes: [
                'root' => 'frise.',
                'list' => 'frise.index',
                'detail' => 'frise.index',
                'add' => $isAge ? 'frise.age.add' : 'frise.entree.add',
            ],
            msg: [
                'entity' => $nom,
                'entity_added' => $isAge ? "L'âge a été ajouté" : "L'entrée a été ajoutée",
                'entity_updated' => $isAge ? "L'âge a été mis à jour" : "L'entrée a été mise à jour",
                'entity_deleted' => $isAge ? "L'âge a été supprimé" : "L'entrée a été supprimée",
                'entity_list' => 'Chronologie du monde',
                'title_add' => $isAge ? 'Ajouter un âge' : 'Ajouter une entrée',
                'title_update' => $isAge ? 'Modifier un âge' : 'Modifier une entrée',
            ],
        );
    }
}
