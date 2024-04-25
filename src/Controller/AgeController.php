<?php

namespace App\Controller;

use App\Entity\Age;
use App\Form\AgeForm;
use App\Repository\AgeRepository;
use App\Repository\PersonnageRepository;
use App\Service\PagerService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_REGLE')]
#[Route('/age', name: 'age.')]
class AgeController extends AbstractController
{
    #[Route('/', name: 'list')]
    public function indexAction(
        Request $request,
        PagerService $pagerService,
        AgeRepository $ageRepository
    ): Response {
        $pagerService->setRequest($request)->setRepository($ageRepository);

// Todo voir si dans le list.twig pour le Thead on peut utiliser les Reposity->translateAttribute
        return $this->render('age/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $ageRepository->searchPaginated($pagerService),
        ]);
    }

    #[Route('/{age}/perso', name: 'perso', requirements: ['age' => Requirement::DIGITS], methods: ['GET'])]
    public function persoAction(
        #[MapEntity] Age $age,
        Request $request,
        PagerService $pagerService,
        PersonnageRepository $personnageRepository
    ): Response {
        $pagerService->setRequest($request)->setRepository($personnageRepository);

        return $this->render('age/perso.twig', [
            'age' => $age,
            'pagerService' => $pagerService,
            'paginator' => $personnageRepository->searchPaginated($pagerService),
        ]);
    }

    #[Route('/{age}/detail', name: 'detail', requirements: ['age' => Requirement::DIGITS], methods: ['GET'])]
    public function detailAction(#[MapEntity] Age $age): RedirectResponse|Response
    {
        return $this->render('age/detail.twig', ['age' => $age]);
    }

    #[Route('/add', name: 'add')]
    public function addAction(Request $request): RedirectResponse|Response
    {
        $age = new Age();

        return $this->handleCreateorUpdate($request, $age);
    }

    #[Route('/{age}/update', name: 'update', requirements: ['age' => Requirement::DIGITS], methods: [
        'DELETE',
        'GET',
        'POST',
    ])]
    public function updateAction(
        Request $request,
        #[MapEntity] Age $age
    ): RedirectResponse|Response {
        return $this->handleCreateorUpdate($request, $age);
    }

    protected function handleCreateorUpdate(Request $request, Age $age): RedirectResponse|Response
    {
        $form = $this->createForm(AgeForm::class, $age);
        $isNew = $this->entityManager->getUnitOfWork()->isInIdentityMap($age);

        if ($isNew) {
            $form->add('update', SubmitType::class, ['label' => 'Sauvegarder'])
                ->add('delete', SubmitType::class, ['label' => 'Supprimer']
                /* TODO un confirm sur tous les delete
                ButtonType::class, [
                'label' => 'Supprimer',
                'attr' => [
                    'value' => 'Submit',
                    'data-toggle' => 'modal',
                    'data-target' => '#confirm-submit',
                    'class' => 'btn btn-default',
                ],
            ]*/
                );
        } else {
            $form->add('save', SubmitType::class, ['label' => 'Sauvegarder'])
                ->add(
                    'save_continue',
                    SubmitType::class,
                    ['label' => 'Sauvegarder & continuer']
                );
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Age $age */
            $age = $form->getData();

            if ($form->has('save_continue') && $form->get('save_continue')->isClicked()) {
                $this->addFlash(
                    'success',
                    sprintf('L\'age %s a été ajouté.', $age->getLabel())
                );
                $this->entityManager->persist($age);
                $this->entityManager->flush();

                return $this->redirectToRoute('age.add');
            }

            if ($form->has('delete') && $form->get('delete')->isClicked()) {
                $this->entityManager->remove($age);
                $this->addFlash('success', 'L\'age a été supprimé.');
            } else {
                $this->entityManager->persist($age);

                if ($form->has('update') && $form->get('update')->isClicked()) {
                    $this->addFlash('success', 'L\'age niveau a été mis à jour.');
                }

                if ($form->has('save') && $form->get('save')->isClicked()) {
                    $this->addFlash('success', 'L\'age niveau a été ajouté.');
                }
            }

            $this->entityManager->flush();

            return $this->redirectToRoute('age.list');
        }

        return $this->render('age/form.twig', [
            'age' => $age,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{age}/delete', name: 'delete', requirements: ['age' => Requirement::DIGITS], methods: [
        'DELETE',
        'GET',
        'POST',
    ])]
    public function deleteAction(#[MapEntity] Age $age): RedirectResponse|Response
    {
        return $this->genericDelete(
            $age,
            'Supprimer un age',
            'L\'age a été supprimée',
            'age.list',
            [
                ['route' => $this->generateUrl('age.list'), 'name' => 'Liste des ages'],
                ['route' => 'age.detail', 'name' => $age->getLabel()],
                ['name' => 'Supprimer un age'],
            ]
        );
    }
}
