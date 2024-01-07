<?php


namespace App\Controller;

use App\Entity\Classe;
use App\Repository\ClasseRepository;
use App\Form\Classe\ClasseForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


/**
 * LarpManager\Controllers\ClasseController.
 *
 * @author kevin
 */
class ClasseController extends AbstractController
{
    /**
     * Présentation des classes.
     */
    #[Route('/classe', name: 'classe.index')]
    public function indexAction(Request $request, ClasseRepository $classeRepository): Response
    {
        $orderBy = $this->getRequestOrder(
            alias: 'c',
            defOrderBy: 'label_masculin',
            allowedFields: $classeRepository->getFieldNames()
        );

        $query = $classeRepository->createQueryBuilder('c')
            ->orderBy(key($orderBy), current($orderBy));

        $classes = $classeRepository->findPaginatedQuery(
            $query->getQuery(), $this->getRequestLimit(100), $this->getRequestPage()
        );

        return $this->render(
            'classe\list.twig',
            [
                'classes' => $classes,
            ]
        );
    }

    /**
     * Ajout d'une classe.
     */
    #[Route('/classe/add', name: 'classe.add')]
    public function addAction(EntityManagerInterface $entityManager, Request $request, ClasseRepository $classeRepository): Response
    {
        $classe = new \App\Entity\Classe();

        $form = $this->createForm(ClasseForm::class, $classe)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $classe = $form->getData();

            $entityManager->persist($classe);
            $entityManager->flush();

            $this->addFlash('success', 'La classe a été ajoutée.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('classe.index', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('classe.add', [], 303);
            }
        }

        return $this->render('classe/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mise à jour d'une classe.
     */
    #[Route('/classe/{id}/update', name: 'classe.update')]
    public function updateAction(EntityManagerInterface $entityManager, Request $request, #[MapEntity] Classe $classe)
    {
        $form = $this->createForm(ClasseForm::class, $classe)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', SubmitType::class, ['label' => 'Supprimer'])
        ;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $classe = $form->getData();

            if ($form->get('update')->isClicked()) {
                $entityManager->persist($classe);
                $entityManager->flush();
                $this->addFlash('success', 'La classe a été mise à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($classe);
                $entityManager->flush();
                $this->addFlash('success', 'La classe a été supprimée.');
            }

            //return $this->redirectToRoute('classe'));
            return $this->redirectToRoute('classe.index', [], 303);
        }

        return $this->render('classe/update.twig', [
            'classe' => $classe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une classe.
     */
    #[Route('/classe/{id}', name: 'classe.detail')]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this.')]
    public function detailAction(EntityManagerInterface $entityManager, int $id)
    {
        $classe = $entityManager->getRepository(Classe::class)->find($id);
        
        return $this->render('classe/detail.twig', ['classe' => $classe]);
    }

    /**
     * Liste des persos d'une classe.
     */
    #[Route('/classe/{id}/perso', name: 'classe.perso')]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this.')]
    public function persoAction(EntityManagerInterface $entityManager, int $id)
    {
        $classe = $entityManager->getRepository(Classe::class)->find($id);

        return $this->render('classe/perso.twig', ['classe' => $classe]);
    }
}
