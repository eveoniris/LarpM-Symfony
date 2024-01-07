<?php


namespace App\Controller;

use App\Entity\Quality;
use Doctrine\Common\Collections\ArrayCollection;
use App\Form\Quality\QualityDeleteForm;
use App\Form\Quality\QualityForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_SCENARISTE')]
class QualityController extends AbstractController
{
    /**
     * Liste les qualitys.
     */
    #[Route('/quality', name: 'quality.list')]
    public function listAction( EntityManagerInterface $entityManager, Request $request)
    {
        $qualities = $entityManager->getRepository('\\'.\App\Entity\Quality::class)->findAll();

        return $this->render('admin/quality/list.twig', [
            'qualities' => $qualities,
        ]);
    }

    /**
     * Ajoute une quality.
     */
    public function addAction( EntityManagerInterface $entityManager, Request $request)
    {
        $form = $this->createForm(QualityForm::class, new Quality())
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quality = $form->getData();

            /*
             * Pour toutes les valeurs de la qualité
             */
            foreach ($quality->getQualityValeurs() as $qualityValeur) {
                $qualityValeur->setQuality($quality);
            }

            $entityManager->persist($quality);
            $entityManager->flush();

           $this->addFlash('success', 'La quality a été enregistrée.');

            return $this->redirectToRoute('quality', [], 303);
        }

        return $this->render('admin/quality/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour une quality.
     */
    public function updateAction( EntityManagerInterface $entityManager, Request $request, Quality $quality)
    {
        $originalQualityValeurs = new ArrayCollection();

        /*
         *  Crée un tableau contenant les objets qualityValeur de la quality
         */
        foreach ($quality->getQualityValeurs() as $qualityValeur) {
            $originalQualityValeurs->add($qualityValeur);
        }

        $form = $this->createForm(QualityForm::class, $quality)
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quality = $form->getData();

            /*
             * Pour toutes les valeurs de la qualité
             */
            foreach ($quality->getQualityValeurs() as $qualityValeur) {
                $qualityValeur->setQuality($quality);
            }

            /*
             *  supprime la relation entre participantHasRestauration et le participant
             */
            foreach ($originalQualityValeurs as $qualityValeur) {
                if (false == $quality->getQualityValeurs()->contains($qualityValeur)) {
                    $entityManager->remove($qualityValeur);
                }
            }

            $entityManager->persist($quality);
            $entityManager->flush();

           $this->addFlash('success', 'La quality a été enregistrée.');

            return $this->redirectToRoute('quality', [], 303);
        }

        return $this->render('admin/quality/update.twig', [
            'quality' => $quality,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime une quality.
     */
    public function deleteAction( EntityManagerInterface $entityManager, Request $request, Quality $quality)
    {
        $form = $this->createForm(QualityDeleteForm::class, $quality)
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quality = $form->getData();
            $entityManager->remove($quality);
            $entityManager->flush();

           $this->addFlash('success', 'La quality a été supprimée.');

            return $this->redirectToRoute('quality', [], 303);
        }

        return $this->render('admin/quality/delete.twig', [
            'quality' => $quality,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Fourni le détail d'une quality.
     */
    public function detailAction( EntityManagerInterface $entityManager, Request $request, Quality $quality)
    {
        return $this->render('admin/quality/detail.twig', [
            'quality' => $quality,
        ]);
    }
}
