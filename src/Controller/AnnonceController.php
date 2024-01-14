<?php


namespace App\Controller;

use App\Entity\Annonce;
use JasonGrimes\Paginator;
use App\Form\AnnonceDeleteForm;
use App\Form\AnnonceForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

#[isGranted('ROLE_REDACTEUR')]
class AnnonceController extends AbstractController
{
    /**
     * Présentation des annonces.
     */
    #[Route('/annonce', name: 'annonce.list')]
    public function listAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $order_by = $request->get('order_by') ?: 'creation_date';
        $order_dir = 'DESC' === $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;

        $criteria = [];

        $repo = $entityManager->getRepository('\\'.\App\Entity\Annonce::class);
        $annonces = $repo->findBy(
            $criteria,
            [$order_by => $order_dir],
            $limit,
            $offset);

        $numResults = $repo->findCount($criteria);

        $paginator = new Paginator($numResults, $limit, $page,
            $app['url_generator']->generate('annonce.list').'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir
        );

        return $this->render('annonce/list.twig', [
            'annonces' => $annonces,
            'paginator' => $paginator,
        ]);
    }

    /**
     * Ajout d'une annonce.
     */
    #[Route('/annonce', name: 'annonce.add')]
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(AnnonceForm::class, new Annonce())
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $annonce = $form->getData();

            $entityManager->persist($annonce);
            $entityManager->flush();

           $this->addFlash('success', 'L\'annonce a été ajoutée.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('annonce.list', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('annonce.add', [], 303);
            }
        }

        return $this->render('annonce/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mise à jour d'une annnonce.
     */
    #[Route('/annonce/update/{id}', name: 'annonce.update')]
    public function updateAction(Request $request, #[MapEntity] Annonce $annonce, EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(AnnonceForm::class, $annonce)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $annonce = $form->getData();

            $annonce->setUpdateDate(new \DateTime('NOW'));

            $entityManager->persist($annonce);
            $entityManager->flush();
           $this->addFlash('success', 'L\'annonce a été mise à jour.');

            return $this->redirectToRoute('annonce.list');
        }

        return $this->render('annonce/update.twig', [
            'annonce' => $annonce,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une annonce.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager, Annonce $annonce)
    {
        return $this->render('annonce/detail.twig', ['annonce' => $annonce]);
    }

    /**
     * Suppression d'une annonce.
     */
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager, Annonce $annonce)
    {
        $form = $this->createForm(AnnonceDeleteForm::class, $annonce)
            ->add('delete', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $annonce = $form->getData();

            $entityManager->remove($annonce);
            $entityManager->flush();

           $this->addFlash('success', 'L\'annonce a été supprimée.');

            return $this->redirectToRoute('annonce.list');
        }

        return $this->render('annonce/delete.twig', [
            'annonce' => $annonce,
            'form' => $form->createView(),
        ]);
    }
}
