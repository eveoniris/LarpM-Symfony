<?php


namespace App\Controller;

use App\Entity\Background;
use JasonGrimes\Paginator;
use App\Form\BackgroundDeleteForm;
use App\Form\BackgroundFindForm;
use App\Form\BackgroundForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_SCENARISTE')]
class BackgroundController extends AbstractController
{
    /**
     * Présentation des backgrounds.
     */
    #[Route('/background', name: 'background.list')]
    public function listAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $order_by = $request->get('order_by') ?: 'id';
        $order_dir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $criteria = [];

        $form = $this->createForm(new BackgroundFindForm());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // TODO
            /*$data = $form->getData();
            $type = $data['type'];
            $value = $data['value'];
            switch ($type){
                case 'Auteur':
                    $criteria[] = "g.nom LIKE '%$value%'";
                    break;
                case 'Groupe':
                    $criteria[] = "u.name LIKE '%$value%'";
                    break;
            }*/
        }

        $repo = $entityManager->getRepository('\\'.\App\Entity\Background::class);
        $backgrounds = $repo->findBy(
            $criteria,
            [$order_by => $order_dir],
            $limit,
            $offset);

        $numResults = $repo->findCount($criteria);

        $paginator = new Paginator($numResults, $limit, $page,
            $app['url_generator']->generate('background.list').'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir
        );

        return $this->render('admin/background/list.twig', [
            'backgrounds' => $backgrounds,
            'paginator' => $paginator,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Impression de tous les backgrounds de groupe.
     */
    public function printAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $gns = $entityManager->getRepository('\\'.\App\Entity\Gn::class)->findActive();
        if (0 == count($gns)) {
            echo 'Erreur : Aucun GN actif trouvé. Veuillez activer le GN en préparation.';
            exit;
        } elseif (count($gns) > 1) {
            echo "Erreur : Il ne peut pas y avoir plus d'un GN actif à la fois. Merci de désactiver le GN précédent.";
            exit;
        }

        $backgrounds = $entityManager->getRepository('\\'.\App\Entity\Background::class)->findBackgrounds($gns[0]->getId());

        return $this->render('admin/background/print.twig', [
            'backgrounds' => $backgrounds,
        ]);
    }

    /**
     * Impression de tous les backgrounds de personnage.
     */
    public function personnagePrintAction(Request $request,  EntityManagerInterface $entityManager)
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

        return $this->render('admin/background/personnagePrint.twig', [
            'groupeGns' => $groupeGns,
        ]);
    }

    /**
     * Ajout d'un background.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $background = new \App\Entity\Background();
        $groupeId = $request->get('groupe');

        if ($groupeId) {
            $groupe = $entityManager->find('\\'.\App\Entity\Groupe::class, $groupeId);
            if ($groupe) {
                $background->setGroupe($groupe);
            }
        }

        $form = $this->createForm(BackgroundForm::class, $background)
            ->add('visibility', 'choice', [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => $app['larp.manager']->getVisibility(),
            ])
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $background = $form->getData();
            $background->setUser($this->getUser());

            $entityManager->persist($background);
            $entityManager->flush();

           $this->addFlash('success', 'Le background a été ajouté.');

            return $this->redirectToRoute('groupe.detail', ['index' => $background->getGroupe()->getId()], [], 303);
        }

        return $this->render('admin/background/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'un background.
     */
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager, Background $background)
    {
        $form = $this->createForm(BackgroundDeleteForm::class, $background)
            ->add('save', 'submit', ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $background = $form->getData();
            $entityManager->remove($background);
            $entityManager->flush();

           $this->addFlash('success', 'Le background a été supprimé.');

            return $this->redirectToRoute('groupe.detail', ['index' => $background->getGroupe()->getId()], [], 303);
        }

        return $this->render('admin/background/delete.twig', [
            'form' => $form->createView(),
            'background' => $background,
        ]);
    }

    /**
     * Mise à jour d'un background.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, Background $background)
    {
        $form = $this->createForm(BackgroundForm::class, $background)
            ->add('visibility', 'choice', [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => $app['larp.manager']->getVisibility(),
            ])
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $background = $form->getData();
            $background->setUpdateDate(new \DateTime('NOW'));

            $entityManager->persist($background);
            $entityManager->flush();

           $this->addFlash('success', 'Le background a été ajouté.');

            return $this->redirectToRoute('groupe.detail', ['index' => $background->getGroupe()->getId()], [], 303);
        }

        return $this->render('admin/background/update.twig', [
            'form' => $form->createView(),
            'background' => $background,
        ]);
    }

    /**
     * Détail d'un background.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager, Background $background)
    {
        return $this->render('admin/background/detail.twig', [
            'background' => $background,
        ]);
    }
}
