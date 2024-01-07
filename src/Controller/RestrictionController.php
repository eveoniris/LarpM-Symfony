<?php

namespace App\Controller;

use App\Entity\Restriction;
use App\Form\RestrictionDeleteForm;
use App\Form\RestrictionForm;
use App\Repository\RestrictionRepository;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RestrictionController extends AbstractController
{
    /**
     * Liste des restrictions.
     */
    #[Route('/restriction/list', name: 'restriction.list')]
    public function listAction(Request $request, RestrictionRepository $repository): Response
    {
        $alias = 'r';

        $orderBy = $this->getRequestOrder(
            defOrderBy: 'label',
            alias: $alias,
            allowedFields: $repository->getFieldNames()
        );

        $paginator = $repository->getPaginator(
            limit: $this->getRequestLimit(25),
            page: $this->getRequestPage(),
            orderBy: $orderBy,
            alias: $alias,
        );

        return $this->render(
            'restriction/list.twig',
            ['paginator' => $paginator]
        );
    }

    /**
     * Imprimer la liste des restrictions.
     */
    #[Route('/restriction/print', name: 'restriction.print')]
    public function printAction(Request $request, RestrictionRepository $repository): Response
    {
        $restrictions = $repository->findAllOrderedByLabel();

        return $this->render('restriction/print.twig', ['restrictions' => $restrictions]);
    }

    /**
     * Télécharger la liste des restrictions alimentaires.
     */
    #[NoReturn]
    #[Route('/restriction/download', name: 'restriction.download')]
    public function downloadAction(Request $request, RestrictionRepository $repository): void
    {
        $restrictions = $repository->findAllOrderedByLabel();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_restrictions_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // header
        fputcsv($output,
            [
                'nom',
                'nombre'], ';');

        foreach ($restrictions as $restriction) {
            $line = [];
            $line[] = mb_convert_encoding((string) $restriction->getLabel(), 'ISO-8859-1');
            $line[] = mb_convert_encoding((string) $restriction->getUsers()->count(), 'ISO-8859-1');
            fputcsv($output, $line, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Ajouter une restriction alimentaire.
     */
    #[Route('/restriction/add', name: 'restriction.add')]
    public function addAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $form = $this->createForm(RestrictionForm::class, new Restriction())
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $restriction = $form->getData();
            $restriction->setAuteur($this->getUser());

            $entityManager->persist($restriction);
            $entityManager->flush();

            $this->addFlash('success', 'La restriction a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('restriction.list', [], 303);
            }
            if ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('restriction.add', [], 303);
            }
        }

        return $this->render('restriction/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une restriction alimentaire.
     */
    #[Route('/restriction/detail/{id}', name: 'restriction.detail')]
    public function detailAction(Request $request, #[MapEntity] Restriction $restriction): Response
    {
        return $this->render('restriction/detail.twig', ['restriction' => $restriction]);
    }

    /**
     * Mise à jour d'un lieu.
     */
    #[Route('/restriction/update/{id}', name: 'restriction.update')]
    public function updateAction(Request $request, #[MapEntity] Restriction $restriction, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $form = $this->createForm(RestrictionForm::class, $restriction)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $restriction = $form->getData();
            $entityManager->persist($restriction);
            $entityManager->flush();

            $this->addFlash('success', 'La restriction alimentaire a été modifié.');

            return $this->redirectToRoute('restriction.list', [], 303);
        }

        return $this->render('restriction/update.twig', [
            'restriction' => $restriction,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'une restriction alimentaire.
     */
    #[Route('/restriction/delete/{id}', name: 'restriction.delete')]
    public function deleteAction(Request $request, #[MapEntity] Restriction $restriction, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RestrictionDeleteForm::class, $restriction)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $restriction = $form->getData();

            $entityManager->remove($restriction);
            $entityManager->flush();

            $this->addFlash('success', 'La restriction alimentaire a été supprimé.');

            return $this->redirectToRoute('restriction.list', [], 303);
        }

        return $this->render('restriction/delete.twig', [
            'restriction' => $restriction,
            'form' => $form->createView(),
        ]);
    }
}
