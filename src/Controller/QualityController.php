<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Quality;
use App\Form\Quality\QualityDeleteType;
use App\Form\Quality\QualityType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SCENARISTE')]
class QualityController extends AbstractController
{
    /**
     * Liste les qualitys.
     */
    #[Route('/quality', name: 'quality.list')]
    public function listAction(EntityManagerInterface $entityManager, Request $request): Response
    {
        $qualities = $entityManager->getRepository('\\' . Quality::class)->findAll();

        return $this->render('quality/list.twig', [
            'qualities' => $qualities,
        ]);
    }

    /**
     * Ajoute une quality.
     */
    #[Route('/quality/add', name: 'quality.add')]
    public function addAction(EntityManagerInterface $entityManager, Request $request): Response|RedirectResponse
    {
        $form = $this->createForm(QualityType::class, new Quality())->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Enregistrer']);

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

            return $this->redirectToRoute('quality.list', [], 303);
        }

        return $this->render('quality/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour une quality.
     */
    #[Route('/quality/{quality}/update', name: 'quality.update')]
    public function updateAction(
        EntityManagerInterface $entityManager,
        Request $request,
        Quality $quality,
    ): Response|RedirectResponse {
        $originalQualityValeurs = new ArrayCollection();

        /*
         *  Crée un tableau contenant les objets qualityValeur de la quality
         */
        foreach ($quality->getQualityValeurs() as $qualityValeur) {
            $originalQualityValeurs->add($qualityValeur);
        }
        // dump($quality);

        $form = $this->createForm(QualityType::class, $quality)->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Enregistrer']);

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
                if (false != $quality->getQualityValeurs()->contains($qualityValeur)) {
                    continue;
                }

                $entityManager->remove($qualityValeur);
            }

            $entityManager->persist($quality);
            $entityManager->flush();

            $this->addFlash('success', 'La quality a été enregistrée.');

            return $this->redirectToRoute('quality.list', [], 303);
        }

        return $this->render('quality/update.twig', [
            'quality' => $quality,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime une quality.
     */
    #[Route('/quality/{quality}/delete', name: 'quality.delete')]
    public function deleteAction(
        EntityManagerInterface $entityManager,
        Request $request,
        Quality $quality,
    ): Response|RedirectResponse {
        $form = $this->createForm(QualityDeleteType::class, $quality)->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quality = $form->getData();
            $entityManager->remove($quality);
            $entityManager->flush();

            $this->addFlash('success', 'La quality a été supprimée.');

            return $this->redirectToRoute('quality.list', [], 303);
        }

        return $this->render('quality/delete.twig', [
            'quality' => $quality,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Fourni le détail d'une quality.
     */
    #[Route('/quality/{quality}/detail', name: 'quality.detail')]
    public function detailAction(EntityManagerInterface $entityManager, Request $request, Quality $quality): Response
    {
        return $this->render('quality/detail.twig', [
            'quality' => $quality,
        ]);
    }
}
