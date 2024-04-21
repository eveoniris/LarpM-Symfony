<?php

namespace App\Controller;

use App\Entity\Level;
use App\Form\LevelForm;
use App\Repository\LevelRepository;
use App\Service\PagerService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_REGLE')]
#[Route('/level', name: 'level.')]
class LevelController extends AbstractController
{
    #[Route('/', name: 'list')]
    public function indexAction(
        Request $request,
        PagerService $pagerService,
        LevelRepository $levelRepository
    ): Response {
        $pagerService->setRequest($request)->setRepository($levelRepository);

        return $this->render('level/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $levelRepository->searchPaginated($pagerService),
        ]);
    }

    #[Route('/add', name: 'add')]
    public function addAction(Request $request): RedirectResponse|Response
    {
        $level = new Level();

        return $this->handleCreateorUpdate($request, $level);
    }

    #[Route('/{level}/update', name: 'update', requirements: ['level' => Requirement::DIGITS], methods: [
        'DELETE',
        'GET',
        'POST',
    ])]
    public function updateAction(
        Request $request,
        #[MapEntity] Level $level
    ): RedirectResponse|Response {
        return $this->handleCreateorUpdate($request, $level);
    }

    protected function handleCreateorUpdate(Request $request, Level $level): RedirectResponse|Response
    {
        $form = $this->createForm(LevelForm::class, $level);
        $isNew = $this->entityManager->getUnitOfWork()->isInIdentityMap($level);

        if ($isNew) {
            $form->add('update', SubmitType::class, ['label' => 'Sauvegarder'])
                ->add('delete', SubmitType::class, ['label' => 'Supprimer']);
        } else {
            $form = $this->createForm(LevelForm::class, $level)
                ->add('save', SubmitType::class, ['label' => 'Sauvegarder'])
                ->add(
                    'save_continue',
                    SubmitType::class,
                    ['label' => 'Sauvegarder & continuer']
                );
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Level $level */
            $level = $form->getData();

            if ($form->has('save_continue') && $form->get('save_continue')->isClicked()) {
                $this->addFlash(
                    'success',
                    sprintf('Le niveau %s a été ajouté.', $level->getIndexLabel())
                );
                $this->entityManager->persist($level);
                $this->entityManager->flush();

                return $this->redirectToRoute('level.add');
            }

            if ($form->has('delete') && $form->get('delete')->isClicked()) {
                $this->entityManager->remove($level);
                $this->addFlash('success', 'Le niveau a été supprimé.');
            } else {
                $this->entityManager->persist($level);

                if ($form->has('update') && $form->get('update')->isClicked()) {
                    $this->addFlash('success', 'Le niveau a été mis à jour.');
                }

                if ($form->has('save') && $form->get('save')->isClicked()) {
                    $this->addFlash('success', 'Le niveau a été ajouté.');
                }
            }

            $this->entityManager->flush();

            return $this->redirectToRoute('level.list');
        }

        return $this->render('level/form.twig', [
            'level' => $level,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{level}/detail', name: 'detail', requirements: ['level' => Requirement::DIGITS], methods: ['GET'])]
    public function detailAction(#[MapEntity] Level $level): RedirectResponse|Response
    {
        return $this->render('level/detail.twig', ['level' => $level]);
    }

    #[Route('/{level}/delete', name: 'delete', requirements: ['level' => Requirement::DIGITS], methods: [
        'DELETE',
        'GET',
        'POST',
    ])]
    public function deleteAction(#[MapEntity] Level $level): RedirectResponse|Response
    {
        return $this->genericDelete(
            $level,
            'Supprimer un niveau',
            'Le niveau a été supprimée',
            'level.list',
            [
                ['route' => $this->generateUrl('level.list'), 'name' => 'Liste des niveaux'],
                ['route' => 'level.detail', 'level' => $level->getId(), 'name' => $level->getLabel()],
                ['name' => 'Supprimer un niveau'],
            ]
        );
    }
}
