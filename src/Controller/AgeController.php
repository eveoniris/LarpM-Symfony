<?php


namespace App\Controller;

use App\Entity\Age;
use App\Form\AgeForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_REGLE')]
class AgeController extends AbstractController
{
    /**
     * Liste des ages.
     *
     * @return View $view
     */
    #[Route('/age', name: 'age.index')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $ages = $entityManager->getRepository('\\'.\App\Entity\Age::class)
            ->findAllOrderedByLabel();

        return $this->render('admin/age/index.twig', ['ages' => $ages]);
    }

    /**
     * Liste des perso ayant cet age.
     */
    public function persoAction(Request $request,  EntityManagerInterface $entityManager, Age $age)
    {
        return $this->render('admin/age/perso.twig', ['age' => $age]);
    }

    /**
     * Detail d'un age.
     *
     * @return View $view
     *
     * @throws LarpManager\Exception\ObjectNotFoundException
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager, Age $age)
    {
        return $this->render('admin/age/detail.twig', ['age' => $age]);
    }

    /**
     * Affiche le formulaire d'ajout d'un age.
     *
     * @return View $view
     */
    public function addViewAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(AgeForm::class, new Age())
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        return $this->render('admin/age/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajout d'un age.
     *
     * @return View $view
     *
     * @throws LarpManager\Exception\RequestInvalid
     */
    public function addPostAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(AgeForm::class, new Age())
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        /*
         * Si la requête est invalide, renvoyer vers une page d'erreur
         */
        if (!$form->isValid()) {
            throw new LarpManager\Exception\RequestInvalidException();
        }

        $age = $form->getData();

        $entityManager->persist($age);
        $entityManager->flush();

       $this->addFlash('success', 'L\'age a été ajouté.');

        /*
         * Si l'utilisateur a cliquer sur "Sauvegarder", on le redirige vers la liste des age
         */
        if ($form->get('save')->isClicked()) {
            return $this->redirectToRoute('age', [], 303);
        }

        // renvoi vers le formulaire d'ajout d'un age
        return $this->redirectToRoute('age.add.view', [], 303);
    }

    /**
     * Afiche le formulaire de mise à jour d'un age.
     *
     * @return View $view
     *
     * @throws LarpManager\Exception\ObjectNotFoundException
     */
    public function updateViewAction(Request $request,  EntityManagerInterface $entityManager, Age $age)
    {
        $form = $this->createForm(AgeForm::class, $age)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', 'button', [
                'label' => 'Supprimer',
                'attr' => [
                    'value' => 'Submit',
                    'data-toggle' => 'modal',
                    'data-target' => '#confirm-submit',
                    'class' => 'btn btn-default',
                ],
            ]);

        return $this->render('admin/age/update.twig', [
            'age' => $age,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour un age.
     *
     * @return View $view
     *
     * @throws LarpManager\Exception\RequestInvalid
     * @throws LarpManager\Exception\ObjectNotFoundException
     */
    public function updatePostAction(Request $request,  EntityManagerInterface $entityManager, Age $age)
    {
        $form = $this->createForm(AgeForm::class, $age)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', 'button', [
                'label' => 'Supprimer',
                'attr' => [
                    'value' => 'Submit',
                    'data-toggle' => 'modal',
                    'data-target' => '#confirm-submit',
                    'class' => 'btn btn-default',
                ],
            ]);

        $form->handleRequest($request);

        /*
         * Si la requête est invalide, renvoyer vers une page d'erreur
         */
        if (!$form->isValid()) {
            throw new LarpManager\Exception\RequestInvalid();
        }

        $age = $form->getData();

        /*
         * Si l'utilisateur a cliqué sur "Sauvegarder", l'age sera sauvegarder dans la base de données
         * Sinon si l'utilisateur a cliqué sur "Supprimer", l'age sera supprimer dans la base de données.
         */
        if ($form->get('update')->isClicked()) {
            $entityManager->persist($age);
            $entityManager->flush();
           $this->addFlash('success', 'L\'age a été mis à jour.');
        } else {
            $entityManager->remove($age);
            $entityManager->flush();
           $this->addFlash('success', 'L\'age a été supprimé.');
        }

        return $this->redirectToRoute('age');
    }
}
