<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Form\Classe\ClasseForm;
use App\Repository\ClasseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
            ->where('c.creation is not null')
            ->orderBy(key($orderBy), current($orderBy));

        $classes = $classeRepository->findPaginatedQuery(
            $query->getQuery(),
            $this->getRequestLimit(100),
            $this->getRequestPage()
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
    public function addAction(
        EntityManagerInterface $entityManager,
        Request $request,
        ClasseRepository $classeRepository
    ): Response {
        $classe = new Classe();

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
    #[Route('/classe/{classe}/update', name: 'classe.update')]
    public function updateAction(
        EntityManagerInterface $entityManager,
        Request $request,
        #[MapEntity] Classe $classe
    ): RedirectResponse|Response {
        $form = $this->createForm(ClasseForm::class, $classe)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', SubmitType::class, ['label' => 'Supprimer']);

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

            // return $this->redirectToRoute('classe'));
            return $this->redirectToRoute('classe.index', [], 303);
        }

        return $this->render('classe/update.twig', [
            'classe' => $classe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * TODO admin OR REGLE acces ?
     */
    #[Route('/classe/{classe}', name: 'classe.detail', requirements: ['classe' => Requirement::DIGITS])]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this.')]
    public function detailAction(#[MapEntity] Classe $classe): Response
    {
        return $this->render('classe/detail.twig', ['classe' => $classe]);
    }

    /**
     * Liste des persos d'une classe.
     */
    #[Route('/classe/{classe}/perso', name: 'classe.perso')]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this.')]
    public function persoAction(EntityManagerInterface $entityManager, int $id)
    {
        $classe = $entityManager->getRepository(Classe::class)->find($id);

        return $this->render('classe/perso.twig', ['classe' => $classe]);
    }

    /**
     * Récupération de l'image d'une classe en fonction du sexe.
     */
    #[Route('/classe/{classe}/image/{sexe}', name: 'classe.image', methods: ['GET'])]
    public function imageAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Classe $classe,
        string $sexe
    ): Response {
        $image = $classe->getImageM();
        if ('F' === $sexe) {
            $image = $classe->getImageF();
        }

        $filename = __DIR__.'/../../assets/img/'.$image;

        $response = new Response(file_get_contents($filename));
        $response->headers->set('Content-Type', 'image/png');

        return $response;
    }
}
