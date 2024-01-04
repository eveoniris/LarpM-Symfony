<?php


namespace App\Controller;

use Doctrine\ORM\Query;
use App\Form\RessourceForm;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\RessourceController.
 *
 * @author kevin
 */
class RessourceController extends AbstractController
{
    /**
     * API: fourni la liste des ressources
     * GET /api/ressource.
     */
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
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\Ressource::class);
        $ressources = $repo->findAll();

        return $this->render('ressource/index.twig', ['ressources' => $ressources]);
    }

    /**
     * Ajout d'une ressource.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $ressource = new \App\Entity\Ressource();

        $form = $this->createForm(RessourceForm::class(), $ressource)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ressource = $form->getData();

            $entityManager->persist($ressource);
            $entityManager->flush();

           $this->addFlash('success', 'La ressource a été ajoutée.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('ressource', [], 303);
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
    public function updateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $ressource = $entityManager->find('\\'.\App\Entity\Ressource::class, $id);

        $form = $this->createForm(RessourceForm::class(), $ressource)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer']);

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

            return $this->redirectToRoute('ressource');
        }

        return $this->render('ressource/update.twig', [
            'ressource' => $ressource,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche de détail d'une ressource.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $ressource = $entityManager->find('\\'.\App\Entity\Ressource::class, $id);

        if ($ressource) {
            return $this->render('ressource/detail.twig', ['ressource' => $ressource]);
        } else {
           $this->addFlash('error', 'La ressource n\'a pas été trouvée.');

            return $this->redirectToRoute('ressource');
        }
    }

    public function detailExportAction(Request $request,  EntityManagerInterface $entityManager): void
    {
    }

    public function exportAction(Request $request,  EntityManagerInterface $entityManager): void
    {
    }
}
