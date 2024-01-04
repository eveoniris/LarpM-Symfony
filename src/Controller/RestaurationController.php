<?php

namespace App\Controller;

use App\Entity\Restauration;
use App\Form\RestaurationDeleteForm;
use App\Form\RestaurationForm;
use App\Repository\RestaurationRepository;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RestaurationController extends AbstractController
{
    /**
     * Liste des restaurations.
     */
    #[Route('/restauration/list', name: 'restauration.list')]
    public function listAction(Request $request, RestaurationRepository $repository): Response
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
            'restauration/list.twig',
            ['paginator' => $paginator]
        );
    }

    /**
     * Imprimer la liste des restaurations.
     */
    #[Route('/restauration/print', name: 'restauration.print')]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this page.')]
    public function printAction(Request $request, RestaurationRepository $repository): Response
    {
        $restaurations = $repository->findAllOrderedByLabel();

        return $this->render('restauration/print.twig', ['restaurations' => $restaurations]);
    }

    /**
     * Télécharger la liste des restaurations alimentaires.
     */
    #[NoReturn]
    #[Route('/restauration/download', name: 'restauration.download')]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this page.')]
    public function downloadAction(Request $request, RestaurationRepository $repository): void
    {
        $restaurations = $repository->findAllOrderedByLabel();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_restaurations_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'wb');

        // header
        fputcsv(
            $output,
            [
                'nom',
                'nombre'], ';');

        foreach ($restaurations as $restauration) {
            $line = [];
            $line[] = mb_convert_encoding((string) $restauration->getLabel(), 'ISO-8859-1');
            $line[] = mb_convert_encoding((string) $restauration->getUsers()->count(), 'ISO-8859-1');
            fputcsv($output, $line, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Ajouter une restauration.
     */
    #[Route('/restauration/add', name: 'restauration.add')]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this page.')]
    public function addAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $form = $this->createForm(RestaurationForm::class, new Restauration())
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $restauration = $form->getData();

            $entityManager->persist($restauration);
            $entityManager->flush();

            $this->addFlash('success', 'La restauration a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('restauration.list', [], 303);
            }
            if ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('restauration.add', [], 303);
            }
        }

        return $this->render('restauration/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'un lieu de restauration.
     */
    #[Route('/restauration/detail/{id}', name: 'restauration.detail')]
    public function detailAction(Request $request, #[MapEntity] Restauration $restauration): Response
    {
        return $this->render('restauration/detail.twig', ['restauration' => $restauration]);
    }

    /**
     * Liste des utilisateurs ayant ce lieu de restauration.
     * TODO: optimize query by pagination
     */
    #[Route('/restauration/{id}/users', name: 'restauration.users')]
    public function usersAction(Request $request, #[MapEntity] Restauration $restauration, RestaurationRepository $repository): Response
    {
        return $this->render(
            'restauration/users.twig',
            [
                'restauration' => $restauration,
                'restaurations' => $repository->getUsersByGn($restauration),
            ]
        );
    }

    /**
     * Liste des utilisateurs ayant ce lieu de restauration.
     * TODO optimize
     */
    #[Route('/restauration/{id}/users-export', name: 'restauration.users.export')]
    #[NoReturn]
    public function usersExportAction(Request $request, #[MapEntity] Restauration $restauration): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_restaurations_'.$restauration->getId().'_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'wb');

        // header
        fputcsv($output,
            [
                'nom',
                'prenom',
                'restriction'], ';');

        foreach ($restauration->getUserByGn() as $UserByGn) {
            foreach ($UserByGn['users'] as $User) {
                $restriction = '';
                foreach ($User->getRestrictions() as $r) {
                    $restriction .= $r->getLabel().' - ';
                }

                $line = [];
                $line[] = mb_convert_encoding((string) $User->getEtatCivil()->getNom(), 'ISO-8859-1');
                $line[] = mb_convert_encoding((string) $User->getEtatCivil()->getPrenom(), 'ISO-8859-1');
                $line[] = mb_convert_encoding($restriction, 'ISO-8859-1');
                fputcsv($output, $line, ';');
            }
        }

        fclose($output);
        exit;
    }

    /**
     * Liste des restrictions alimentaires.
     */
    #[Route('/restauration/{id}/restrictions', name: 'restauration.restrictions')]
    public function restrictionsAction(Request $request, #[MapEntity] Restauration $restauration): Response
    {
        return $this->render(
            'restauration/restrictions.twig', [
                'restauration' => $restauration,
            ]
        );
    }

    /**
     * Mise à jour d'un lieu de restauration.
     */
    #[Route('/restauration/{id}/update', name: 'restauration.update')]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this page.')]
    public function updateAction(Request $request, #[MapEntity] Restauration $restauration, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RestaurationForm::class, $restauration)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $restauration = $form->getData();
            $entityManager->persist($restauration);
            $entityManager->flush();

            $this->addFlash('success', 'La restauration alimentaire a été modifié.');

            return $this->redirectToRoute('restauration.list', [], 303);
        }

        return $this->render(
            'restauration/update.twig', [
                'restauration' => $restauration,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Suppression d'un lieu de restauration.
     */
    #[Route('/restauration/{id}/delete', name: 'restauration.delete')]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this page.')]
    public function deleteAction(Request $request, #[MapEntity] Restauration $restauration, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $form = $this->createForm(RestaurationDeleteForm::class, $restauration)
            ->add('save', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $restauration = $form->getData();

            $entityManager->remove($restauration);
            $entityManager->flush();

            $this->addFlash('success', 'Le lieu de restauration a été supprimé.');

            return $this->redirectToRoute('restauration.list', [], 303);
        }

        return $this->render('restauration/delete.twig', [
            'restauration' => $restauration,
            'form' => $form->createView(),
        ]);
    }
}
