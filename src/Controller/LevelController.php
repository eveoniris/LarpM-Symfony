<?php


namespace App\Controller;

use App\Form\LevelForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_REGLE')]
class LevelController extends AbstractController
{
    /**
     * Liste les niveaux.
     */
    #[Route('/level', name: 'level.index')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\Level::class);
        $levels = $repo->findAll();

        return $this->render('level/index.twig', ['levels' => $levels]);
    }

    /**
     * Ajoute un niveau.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $level = new \App\Entity\Level();

        $form = $this->createForm(LevelForm::class, $level)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmited() && $form->isValid()) {
            $level = $form->getData();

            $entityManager->persist($level);
            $entityManager->flush();

           $this->addFlash('success', 'Le niveau a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('level', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('level.add', [], 303);
            }
        }

        return $this->render('level/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour un niveau.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $level = $entityManager->find('\\'.\App\Entity\Level::class, $id);

        $form = $this->createForm(LevelForm::class, $level)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $level = $form->getData();

            if ($form->get('update')->isClicked()) {
                $entityManager->persist($level);
                $entityManager->flush();
               $this->addFlash('success', 'Le niveau a été mis à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($level);
                $entityManager->flush();
               $this->addFlash('success', 'Le niveau a été supprimé.');
            }

            return $this->redirectToRoute('level');
        }

        return $this->render('level/update.twig', [
            'level' => $level,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Detail d'un niveau.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $level = $entityManager->find('\\'.\App\Entity\Level::class, $id);

        if ($level) {
            return $this->render('level/detail.twig', ['level' => $level]);
        } else {
           $this->addFlash('error', 'La niveau n\'a pas été trouvé.');

            return $this->redirectToRoute('level');
        }
    }
}
