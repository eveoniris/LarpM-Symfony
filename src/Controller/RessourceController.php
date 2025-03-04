<?php


namespace App\Controller;

use Doctrine\ORM\Query;
use App\Entity\Ressource;
use App\Form\RessourceForm;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

#[isGranted('ROLE_SCENARISTE')]
class RessourceController extends AbstractController
{
    /**
     * API: fourni la liste des ressources
     * GET /api/ressource.
     */
    #[Route('/api/ressource', name: 'api.ressource')]
    public function apiListAction(Request $request,  EntityManagerInterface $entityManager): JsonResponse
    {
        $qb = $entityManager->createQueryBuilder();
        $qb->select('Ressource')
            ->from('\\'.\App\Entity\Ressource::class, 'Ressource');

        $query = $qb->getQuery();

        $ressources = $query->getResult(Query::HYDRATE_ARRAY);

        return new JsonResponse($ressources);
    }

    /**
     * Liste des ressources.
     */
    #[Route('/ressource', name: 'ressource.index')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager): Response
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\Ressource::class);
        $ressources = $repo->findAll();

        return $this->render('ressource/index.twig', ['ressources' => $ressources]);
    }

    /**
     * Ajout d'une ressource.
     */
    #[Route('/ressource/add', name: 'ressource.add')]
    public function addAction(Request $request,  EntityManagerInterface $entityManager): Response|RedirectResponse
    {
        $ressource = new \App\Entity\Ressource();

        $form = $this->createForm(RessourceForm::class, $ressource)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ressource = $form->getData();

            $entityManager->persist($ressource);
            $entityManager->flush();

           $this->addFlash('success', 'La ressource a été ajoutée.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('ressource.index', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('ressource.add', [], 303);
            }
        }

        return $this->render('ressource/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mise à jour d'une ressource.
     */
    #[Route('/ressource/{ressource}/update', name: 'ressource.update')]
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, Ressource $ressource): Response|RedirectResponse
    {
        $form = $this->createForm(RessourceForm::class, $ressource)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ressource = $form->getData();

            if ($form->get('update')->isClicked()) {
                $entityManager->persist($ressource);
                $entityManager->flush();
               $this->addFlash('success', 'La ressource a été mise à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($ressource);
                $entityManager->flush();
               $this->addFlash('success', 'La ressource a été supprimée.');
            }

            return $this->redirectToRoute('ressource.index');
        }

        return $this->render('ressource/update.twig', [
            'ressource' => $ressource,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche de détail d'une ressource.
     */
    #[Route('/ressource/{ressource}/detail', name: 'ressource.detail')]
    public function detailAction(Request $request, EntityManagerInterface $entityManager, Ressource $ressource): Response|RedirectResponse
    {
        if ($ressource) {
            return $this->render('ressource/detail.twig', ['ressource' => $ressource]);
        } else {
           $this->addFlash('error', 'La ressource n\'a pas été trouvée.');

            return $this->redirectToRoute('ressource.index');
        }
    }

    public function detailExportAction(Request $request,  EntityManagerInterface $entityManager): void
    {
    }

    public function exportAction(Request $request,  EntityManagerInterface $entityManager): void
    {
    }
}
