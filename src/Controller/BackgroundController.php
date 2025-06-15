<?php

namespace App\Controller;

use App\Entity\Background;
use App\Entity\Groupe;
use App\Enum\Role;
use App\Form\BackgroundDeleteForm;
use App\Form\BackgroundFindForm;
use App\Form\BackgroundForm;
use App\Repository\BackgroundRepository;
use App\Repository\GnRepository;
use App\Repository\GroupeGnRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class BackgroundController extends AbstractController
{
    /**
     * Ajout d'un background.
     */
    #[Route('/background/{groupe}/add', name: 'background.add')]
    #[IsGranted('ROLE_SCENARISTE')]
    public function addAction(
        Request $request,
        #[MapEntity] Groupe $groupe,
    ): RedirectResponse|Response {
        $background = new Background();
        $background->setGroupe($groupe);

        $form = $this->createForm(BackgroundForm::class, $background, ['groupeId' => $groupe->getId()])
            ->add('visibility', ChoiceType::class, [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => [
                    'Seuls les scénaristes peuvent voir ceci' => 'PRIVATE',
                    'Tous les joueurs peuvent voir ceci' => 'PUBLIC',
                    'Seuls les membres du groupe peuvent voir ceci' => 'GROUPE_MEMBER',
                    'Seul le chef de groupe peut voir ceci' => 'GROUPE_OWNER',
                    'Seul l\'auteur peut voir ceci' => 'AUTHOR',
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $background = $form->getData();
            $background->setUser($this->getUser());

            $this->entityManager->persist($background);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le background a été ajouté.');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $background->getGroupe()->getId()], 303);
        }

        return $this->render('background/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'un background.
     */
    #[Route('/background/{background}/delete', name: 'background.delete')]
    #[IsGranted('ROLE_SCENARISTE')]
    public function deleteAction(Request $request, EntityManagerInterface $entityManager, Background $background)
    {
        $form = $this->createForm(BackgroundDeleteForm::class, $background)
            ->add('save', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $background = $form->getData();
            $entityManager->remove($background);
            $entityManager->flush();

            $this->addFlash('success', 'Le background a été supprimé.');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $background->getGroupe()->getId()], 303);
        }

        return $this->render('background/delete.twig', [
            'form' => $form->createView(),
            'background' => $background,
        ]);
    }

    /**
     * Détail d'un background.
     */
    #[Route('/background/{background}', name: 'background.detail')]
    public function detailAction(#[MapEntity] Background $background): Response
    {
        if ($this->groupeService->isUserIsGroupeResponsable($background->getGroupe())) {
            $isMembre = true;
        } else {
            $isMembre = $this->groupeService->isUserIsGroupeMember($background->getGroupe());
        }

        if (!$isMembre && !$this->isGranted(Role::SCENARISTE->value)) {
            throw new AccessDeniedHttpException();
        }

        return $this->render('background/detail.twig', [
            'background' => $background,
        ]);
    }

    /**
     * Présentation des backgrounds.
     */
    #[Route('/background', name: 'background.list')]
    #[IsGranted('ROLE_SCENARISTE')]
    public function listAction(Request $request, EntityManagerInterface $entityManager)
    {
        $order_by = $request->get('order_by') ?: 'id';
        $order_dir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $type = null;
        $value = null;

        $form = $this->createForm(BackgroundFindForm::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['value'];
        }

        $repo = $entityManager->getRepository('\\'.Background::class);
        $backgrounds = $repo->findList(
            $type,
            $value,
            ['by' => $order_by, 'dir' => $order_dir],
            $limit,
            $offset,
        );

        $paginator = $repo->findPaginatedQuery(
            $backgrounds,
            $this->getRequestLimit(),
            $this->getRequestPage(),
        );

        return $this->render('background/list.twig', [
            'paginator' => $paginator,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Impression de tous les backgrounds de personnage.
     */
    #[Route('/background/personnage/print', name: 'background.personnage.print')]
    #[IsGranted('ROLE_SCENARISTE')]
    public function personnagePrintAction(GnRepository $gnRepository, GroupeGnRepository $groupeGnRepository)
    {
        $gns = $gnRepository->findActive();
        if (0 == count($gns)) {
            echo 'Erreur : Aucun GN actif trouvé. Veuillez activer le GN en préparation.';
            exit;
        }
        if (count($gns) > 1) {
            echo "Erreur : Il ne peut pas y avoir plus d'un GN actif à la fois. Merci de désactiver le GN précédent.";
            exit;
        }

        $groupeGns = $groupeGnRepository->findByGn($gns[0]->getId());

        return $this->render('background/personnagePrint.twig', [
            'groupeGns' => $groupeGns,
        ]);
    }

    /**
     * Impression de tous les backgrounds de groupe.
     */
    #[Route('/background/print', name: 'background.print')]
    #[IsGranted('ROLE_SCENARISTE')]
    public function printAction(GnRepository $gnRepository, BackgroundRepository $backgroundRepository)
    {
        $gns = $gnRepository->findActive();
        if (0 == count($gns)) {
            echo 'Erreur : Aucun GN actif trouvé. Veuillez activer le GN en préparation.';
            exit;
        }
        if (count($gns) > 1) {
            echo "Erreur : Il ne peut pas y avoir plus d'un GN actif à la fois. Merci de désactiver le GN précédent.";
            exit;
        }

        $backgrounds = $backgroundRepository->findBackgrounds($gns[0]->getId());

        return $this->render('background/print.twig', [
            'backgrounds' => $backgrounds,
        ]);
    }

    /**
     * Mise à jour d'un background.
     */
    #[Route('/background/{background}/update', name: 'background.update')]
    #[IsGranted('ROLE_SCENARISTE')]
    public function updateAction(Request $request, EntityManagerInterface $entityManager, Background $background)
    {
        $form = $this->createForm(BackgroundForm::class, $background)
            ->add('visibility', ChoiceType::class, [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => [
                    'Seuls les scénaristes peuvent voir ceci' => 'PRIVATE',
                    'Tous les joueurs peuvent voir ceci' => 'PUBLIC',
                    'Seuls les membres du groupe peuvent voir ceci' => 'GROUPE_MEMBER',
                    'Seul le chef de groupe peut voir ceci' => 'GROUPE_OWNER',
                    'Seul l\'auteur peut voir ceci' => 'AUTHOR',
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $background = $form->getData();
            $background->setUpdateDate(new \DateTime('NOW'));

            $entityManager->persist($background);
            $entityManager->flush();

            $this->addFlash('success', 'Le background a été ajouté.');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $background->getGroupe()->getId()], 303);
        }

        return $this->render('background/update.twig', [
            'form' => $form->createView(),
            'background' => $background,
        ]);
    }
}
