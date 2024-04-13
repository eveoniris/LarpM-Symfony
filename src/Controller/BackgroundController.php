<?php

namespace App\Controller;

use App\Entity\Background;
use App\Entity\Groupe;
use App\Form\BackgroundDeleteForm;
use App\Form\BackgroundFindForm;
use App\Form\BackgroundForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SCENARISTE')]
class BackgroundController extends AbstractController
{
    /**
     * Présentation des backgrounds.
     */
    #[Route('/background', name: 'background.list')]
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
            $offset
        );
        
        $paginator = $repo->findPaginatedQuery(
            $backgrounds, 
            $this->getRequestLimit(),
            $this->getRequestPage()
        );

        return $this->render('background/list.twig', [
            'paginator' => $paginator,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Impression de tous les backgrounds de groupe.
     */
    #[Route('/background/print', name: 'background.print')]
    public function printAction(Request $request, EntityManagerInterface $entityManager)
    {
        $gns = $entityManager->getRepository('\\'.\App\Entity\Gn::class)->findActive();
        if (0 == count($gns)) {
            echo 'Erreur : Aucun GN actif trouvé. Veuillez activer le GN en préparation.';
            exit;
        } elseif (count($gns) > 1) {
            echo "Erreur : Il ne peut pas y avoir plus d'un GN actif à la fois. Merci de désactiver le GN précédent.";
            exit;
        }

        $backgrounds = $entityManager->getRepository('\\'.Background::class)->findBackgrounds($gns[0]->getId());

        return $this->render('background/print.twig', [
            'backgrounds' => $backgrounds,
        ]);
    }

    /**
     * Impression de tous les backgrounds de personnage.
     */
    #[Route('/background/personnage/print', name: 'background.personnage.print')]
    public function personnagePrintAction(Request $request, EntityManagerInterface $entityManager)
    {
        $gns = $entityManager->getRepository('\\'.\App\Entity\Gn::class)->findActive();
        if (0 == count($gns)) {
            echo 'Erreur : Aucun GN actif trouvé. Veuillez activer le GN en préparation.';
            exit;
        } elseif (count($gns) > 1) {
            echo "Erreur : Il ne peut pas y avoir plus d'un GN actif à la fois. Merci de désactiver le GN précédent.";
            exit;
        }

        $groupeGns = $entityManager->getRepository('\\'.\App\Entity\GroupeGn::class)->findByGn($gns[0]->getId());

        return $this->render('background/personnagePrint.twig', [
            'groupeGns' => $groupeGns,
        ]);
    }

    /**
     * Ajout d'un background.
     */
    #[Route('/background/add', name: 'background.add')]
    public function addAction(Request $request, EntityManagerInterface $entityManager)
    {
        $groupeId = $request->get('groupe');
        $background = new Background();

        if ( $groupeId )
		{
			$groupe = $entityManager->find('\App\Entity\Groupe', $groupeId);
			if ( $groupe ) $background->setGroupe($groupe);
		}

        $form = $this->createForm(BackgroundForm::class, $background, ['groupeId' => $groupeId])
            ->add('visibility', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => array(
                    'Seul les scénaristes peuvent voir ceci' => 'PRIVATE',
                    'Tous les joueurs peuvent voir ceci' => 'PUBLIC',
                    'Seuls les membres du groupe peuvent voir ceci' => 'GROUPE_MEMBER',
                    'Seul le chef de groupe peut voir ceci' => 'GROUPE_OWNER',
                    'Seul l\'auteur peut voir ceci' => 'AUTHOR',
                ),
            ])
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $background = $form->getData();
            $background->setUser($this->getUser());

            $entityManager->persist($background);
            $entityManager->flush();

            $this->addFlash('success', 'Le background a été ajouté.');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $background->getGroupe()->getId()], 303);
        }

        return $this->render('background/add.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Suppression d'un background.
     */
    #[Route('/background/{background}/delete', name: 'background.delete')]
    public function deleteAction(Request $request, EntityManagerInterface $entityManager, Background $background)
    {
        $form = $this->createForm(BackgroundDeleteForm::class, $background)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

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
     * Mise à jour d'un background.
     */
    #[Route('/background/{background}/update', name: 'background.update')]
    public function updateAction(Request $request, EntityManagerInterface $entityManager, Background $background)
    {
        $form = $this->createForm(BackgroundForm::class, $background)
            ->add('visibility', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => array(
                    'Seul les scénaristes peuvent voir ceci' => 'PRIVATE',
                    'Tous les joueurs peuvent voir ceci' => 'PUBLIC',
                    'Seuls les membres du groupe peuvent voir ceci' => 'GROUPE_MEMBER',
                    'Seul le chef de groupe peut voir ceci' => 'GROUPE_OWNER',
                    'Seul l\'auteur peut voir ceci' => 'AUTHOR',
                ),
            ])
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

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

    /**
     * Détail d'un background.
     */
    #[Route('/background/{background}', name: 'background.detail')]
    public function detailAction(Request $request, EntityManagerInterface $entityManager, Background $background)
    {
        return $this->render('background/detail.twig', [
            'background' => $background,
        ]);
    }
}
