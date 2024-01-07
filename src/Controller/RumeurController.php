<?php


namespace App\Controller;

use App\Entity\Rumeur;
use JasonGrimes\Paginator;
use App\Form\Rumeur\RumeurDeleteForm;
use App\Form\Rumeur\RumeurFindForm;
use App\Form\Rumeur\RumeurForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_SCENARISTE')]
class RumeurController extends AbstractController
{
    /**
     * Liste de toutes les rumeurs.
     */
    #[Route('/rumeur', name: 'rumeur.list')]
    public function listAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $order_by = $request->get('order_by') ?: 'id';
        $order_dir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $type = null;
        $value = null;

        $form = $this->createForm(new RumeurFindForm())->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['search'];
        }

        $repo = $entityManager->getRepository('\\'.\App\Entity\Rumeur::class);

        $rumeurs = $repo->findList(
            $type,
            $value,
            ['by' => $order_by, 'dir' => $order_dir],
            $limit,
            $offset);

        $numResults = $repo->findCount($type, $value);

        $paginator = new Paginator($numResults, $limit, $page,
            $app['url_generator']->generate('rumeur.list').'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir
        );

        return $this->render('admin/rumeur/list.twig', [
            'form' => $form->createView(),
            'rumeurs' => $rumeurs,
            'paginator' => $paginator,
        ]);
    }

    /**
     * Lire une rumeur.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager, Rumeur $rumeur)
    {
        return $this->render('admin/rumeur/detail.twig', [
            'rumeur' => $rumeur,
        ]);
    }

    /**
     * Ajouter une rumeur.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $rumeur = new Rumeur();
        $form = $this->createForm(RumeurForm::class, $rumeur)
            ->add('add', 'submit', ['label' => 'Ajouter la rumeur'])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rumeur = $form->getData();
            $rumeur->setUser($this->getUser());

            $entityManager->persist($rumeur);
            $entityManager->flush();

           $this->addFlash('success', 'Votre rumeur a été ajoutée.');

            return $this->redirectToRoute('rumeur.list', [], 303);
        }

        return $this->render('admin/rumeur/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mettre à jour une rumeur.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, Rumeur $rumeur)
    {
        $form = $this->createForm(RumeurForm::class, $rumeur)
            ->add('enregistrer', 'submit', ['label' => 'Enregistrer'])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rumeur = $form->getData();
            $rumeur->setUpdateDate(new \DateTime('NOW'));

            $entityManager->persist($rumeur);
            $entityManager->flush();

           $this->addFlash('success', 'Votre rumeur a été modifiée.');

            return $this->redirectToRoute('rumeur.detail', ['rumeur' => $rumeur->getId()], [], 303);
        }

        return $this->render('admin/rumeur/update.twig', [
            'form' => $form->createView(),
            'rumeur' => $rumeur,
        ]);
    }

    /**
     * Supression d'une rumeur.
     */
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager, Rumeur $rumeur)
    {
        $form = $this->createForm(RumeurDeleteForm::class, $rumeur)
            ->add('supprimer', 'submit', ['label' => 'Supprimer'])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rumeur = $form->getData();
            $entityManager->remove($rumeur);
            $entityManager->flush();

           $this->addFlash('success', 'La rumeur a été supprimée.');

            return $this->redirectToRoute('rumeur.list', [], 303);
        }

        return $this->render('admin/rumeur/delete.twig', [
            'form' => $form->createView(),
            'rumeur' => $rumeur,
        ]);
    }
}
