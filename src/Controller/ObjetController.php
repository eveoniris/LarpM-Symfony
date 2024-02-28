<?php

namespace App\Controller;

use App\Entity\Item;
use App\Entity\Objet;
use App\Form\Item\ItemDeleteForm;
use App\Form\Item\ItemForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse as RedirectResponseAlias;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ObjetController extends AbstractController
{
    /**
     * Présentation des objets de jeu.
     */
    #[Route('/item', name: 'item.index')]
    public function indexAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $repo = $entityManager->getRepository('\\'.Item::class);
        $items = $repo->findAll();

        return $this->render('objet/index.twig', [
            'items' => $items,
        ]);
    }

    /**
     * Impression d'une etiquette.
     */
    #[Route('/item/print', name: 'item.print')]
    public function printAction(Request $request, EntityManagerInterface $entityManager, Item $item): Response
    {
        return $this->render('objet/print.twig', [
            'item' => $item,
        ]);
    }

    /**
     * Impression de toutes les etiquettes.
     */
    #[Route('/item/print-all', name: 'item.print-all')]
    public function printAllAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $repo = $entityManager->getRepository('\\'.Item::class);
        $items = $repo->findAll();

        return $this->render('objet/printAll.twig', [
            'items' => $items,
        ]);
    }

    /**
     * Impression de tous les objets avec photo.
     */
    #[Route('/item/print-photo', name: 'item.print-photo')]
    public function printPhotoAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $repo = $entityManager->getRepository('\\'.Item::class);
        $items = $repo->findAll();

        return $this->render('objet/printPhoto.twig', [
            'items' => $items,
        ]);
    }

    /**
     * Sortie CSV.
     */
    #[Route('/item/print-csv', name: 'item.print-csv')]
    public function printCsvAction(Request $request, EntityManagerInterface $entityManager): void
    {
        $repo = $entityManager->getRepository('\\'.Item::class);
        $items = $repo->findAll();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_objets_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // header
        fputcsv($output,
            [
                'numéro',
                'identification',
                'label',
                'description',
                'special',
                'groupe',
                'personnage',
                'rangement',
                'proprietaire',
            ], ';');

        foreach ($items as $item) {
            $line = [];
            $line[] = mb_convert_encoding((string) $item->getNumero(), 'ISO-8859-1');
            $line[] = mb_convert_encoding($item->getQuality()->getNumero().$item->getIdentification(), 'ISO-8859-1');
            $line[] = mb_convert_encoding((string) $item->getlabel(), 'ISO-8859-1');
            $line[] = mb_convert_encoding(html_entity_decode(strip_tags((string) $item->getDescription())), 'ISO-8859-1');
            $line[] = mb_convert_encoding(html_entity_decode(strip_tags((string) $item->getSpecial())), 'ISO-8859-1');

            $groupes = '';
            foreach ($item->getGroupes() as $groupe) {
                $groupes = $groupe->getNom().', ';
            }

            $line[] = mb_convert_encoding($groupes, 'ISO-8859-1');

            $personnages = '';
            foreach ($item->getPersonnages() as $personnage) {
                $personnages = $personnage->getNom().', ';
            }

            $line[] = mb_convert_encoding($personnages, 'ISO-8859-1');

            $objet = $item->getObjet();
            if ($objet) {
                $line[] = $objet->getRangement() ? mb_convert_encoding((string) $objet->getRangement()->getAdresse(), 'ISO-8859-1') : '';

                $line[] = $objet->getProprietaire() ? mb_convert_encoding((string) $objet->getProprietaire()->getNom(), 'ISO-8859-1') : '';
            } else {
                $line[] = '';
                $line[] = '';
            }

            fputcsv($output, $line, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Création d'un nouvel objet de jeu.
     */
    #[Route('/item/new/{objet}', name: 'item.new')]
    public function newAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Objet $objet): RedirectResponseAlias|Response
    {
        $item = new Item();
        $item->setObjet($objet);

        $form = $this->createForm(ItemForm::class, $item);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $item = $form->getData();

            // si le numéro est vide, générer un numéro en suivant l'ordre
            $numero = $item->getNumero();
            if (!$numero) {
                $repo = $entityManager->getRepository('\\'.Item::class);
                $numero = $repo->findNextNumero();
                if (!$numero) {
                    $numero = 0;
                }

                $item->setNumero($numero);
            }

            // en fonction de l'identification choisie, choisir un numéro d'identification
            $identification = $item->getIdentification();
            switch ($identification) {
                case 1:
                    $identification = sprintf('%02d', mt_rand(1, 10));
                    $item->setIdentification($identification);
                    break;
                case 11:
                    $identification = mt_rand(11, 20);
                    $item->setIdentification($identification);
                    break;
                case 81:
                    $identification = mt_rand(81, 99);
                    $item->setIdentification($identification);
                    break;
            }

            $entityManager->persist($item);
            $entityManager->flush();

            $this->addFlash('success', 'L\'objet de jeu a été créé');

            return $this->redirectToRoute('items', [], 303);
        }

        return $this->render('objet/new.twig', [
            'objet' => $objet,
            'item' => $item,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'un objet de jeu.
     */
    #[Route('/item/{item}', name: 'item.detail')]
    public function detailAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Item $item): Response
    {
        return $this->render('objet/detail.twig', [
            'item' => $item,
        ]);
    }

    /**
     * Mise à jour d'un objet de jeu.
     */
    #[Route('/item/{item}/update', name: 'item.update')]
    public function updateAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Item $item): RedirectResponseAlias|Response
    {
        $form = $this->createForm(ItemForm::class, $item);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($item);
            $entityManager->flush();

            // en fonction de l'identification choisie, choisir un numéro d'identification
            $identification = $item->getIdentification();
            switch ($identification) {
                case 1:
                    $identification = sprintf('%02d', mt_rand(1, 10));
                    $item->setIdentification($identification);
                    break;
                case 11:
                    $identification = mt_rand(11, 20);
                    $item->setIdentification($identification);
                    break;
                case 81:
                    $identification = mt_rand(81, 99);
                    $item->setIdentification($identification);
                    break;
            }

            $this->addFlash('success', 'L\'objet de jeu a été sauvegardé');

            return $this->redirectToRoute('items', [], 303);
        }

        return $this->render('objet/update.twig', [
            'item' => $item,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'un objet de jeu.
     */
    public function deleteAction(Request $request, EntityManagerInterface $entityManager, Item $item): RedirectResponseAlias|Response
    {
        $form = $this->createForm(ItemDeleteForm::class, $item);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->remove($item);
            $entityManager->flush();

            $this->addFlash('success', 'L\'objet de jeu a été supprimé');

            return $this->redirectToRoute('items', [], 303);
        }

        return $this->render('objet/delete.twig', [
            'item' => $item,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Lier un objet de jeu à un groupe/personnage/lieu.
     */
    public function linkAction(Request $request, EntityManagerInterface $entityManager, Item $item): RedirectResponseAlias|Response
    {
        $form = $this->createForm(ItemLinkForm::class, $item);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($item);
            $entityManager->flush();

            $this->addFlash('success', 'L\'objet de jeu a été créé');

            return $this->redirectToRoute('objet', [], 303);
        }

        return $this->render('objet/link.twig', [
            'item' => $item,
            'form' => $form->createView(),
        ]);
    }
}
