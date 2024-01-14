<?php


namespace App\Controller;

use App\Form\CompetenceFamilyForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_REGLE')]
class CompetenceFamilyController extends AbstractController
{
    /**
     * Liste les famille de competence.
     */
    #[Route('/competenceFamily', name: 'competenceFamily.index')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\CompetenceFamily::class);
        $competenceFamilies = $repo->findAllOrderedByLabel();

        return $this->render('competenceFamily/index.twig', ['competenceFamilies' => $competenceFamilies]);
    }

    /**
     * Ajoute une famille de competence.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $competenceFamily = new \App\Entity\CompetenceFamily();

        $form = $this->createForm(CompetenceFamilyForm::class, $competenceFamily)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $competenceFamily = $form->getData();

            $entityManager->persist($competenceFamily);
            $entityManager->flush();

           $this->addFlash('success', 'La famille de compétence a été ajoutée.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('competence.family', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('competence.family.add', [], 303);
            }
        }

        return $this->render('competenceFamily/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour une famille de compétence.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $competenceFamily = $entityManager->find('\\'.\App\Entity\CompetenceFamily::class, $id);

        $form = $this->createForm(CompetenceFamilyForm::class, $competenceFamily)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $competenceFamily = $form->getData();

            if ($form->get('update')->isClicked()) {
                $entityManager->persist($competenceFamily);
                $entityManager->flush();
               $this->addFlash('success', 'La famille de compétence a été mis à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($competenceFamily);
                $entityManager->flush();
               $this->addFlash('success', 'La famille de compétence a été supprimé.');
            }

            return $this->redirectToRoute('competence.family');
        }

        return $this->render('competenceFamily/update.twig', [
            'competenceFamily' => $competenceFamily,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Detail d'une famille de compétence.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $competenceFamily = $entityManager->find('\\'.\App\Entity\CompetenceFamily::class, $id);

        if ($competenceFamily) {
            return $this->render('competenceFamily/detail.twig', ['competenceFamily' => $competenceFamily]);
        } else {
           $this->addFlash('error', 'La famille de compétence n\'a pas été trouvé.');

            return $this->redirectToRoute('competence.family');
        }
    }
}
