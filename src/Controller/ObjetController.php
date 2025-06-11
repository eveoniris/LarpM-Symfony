<?php

namespace App\Controller;

use App\Entity\Item;
use App\Entity\Objet;
use App\Enum\Role;
use App\Form\Item\ItemDeleteForm;
use App\Form\Item\ItemForm;
use App\Form\Item\ItemLinkForm;
use App\Repository\ItemRepository;
use App\Security\MultiRolesExpression;
use App\Service\PagerService;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse as RedirectResponseAlias;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new MultiRolesExpression(Role::ORGA, Role::REGLE, Role::SCENARISTE))]
#[Route('/item', name: 'item.')]
class ObjetController extends AbstractController
{
    /**
     * Suppression d'un objet de jeu.
     */
    #[Route('/{item}/delete', name: 'delete')]
    public function deleteAction(
        Request $request,
        #[MapEntity] Item $item,
    ): RedirectResponseAlias|Response {
        $form = $this->createForm(ItemDeleteForm::class, $item);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->remove($item);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'objet de jeu a été supprimé');

            return $this->redirectToRoute('item.index', [], 303);
        }

        return $this->render('objet/delete.twig', [
            'item' => $item,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'un objet de jeu.
     */
    #[Route('/{item}', name: 'objet', requirements: ['item' => Requirement::DIGITS])]
    #[Route('/{item}/detail', name: 'detail', requirements: ['item' => Requirement::DIGITS])]
    #[Route('/objet/{item}/detail', name: 'objet.detail', requirements: ['item' => Requirement::DIGITS])] // Larp v1 route
    public function detailAction(
        #[MapEntity] Item $item,
    ): Response {
        return $this->render('objet/detail.twig', [
            'item' => $item,
        ]);
    }

    /**
     * Présentation des objets de jeu.
     */
    #[Route('/', name: 'index')]
    #[Route('/', name: 'list')]
    public function indexAction(Request $request, PagerService $pagerService, ItemRepository $itemRepository): Response
    {
        $pagerService->setRequest($request)->setRepository($itemRepository)->setLimit(25);

        return $this->render('objet/index.twig', [
            'pagerService' => $pagerService,
            'paginator' => $itemRepository->searchPaginated($pagerService),
        ]);
    }

    /**
     * Lier un objet de jeu à un groupe/personnage/lieu.
     */
    #[Route('/{item}/link', name: 'link')]
    public function linkAction(
        Request $request,
        Item $item,
    ): RedirectResponseAlias|Response {
        $form = $this->createForm(ItemLinkForm::class, $item);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($item);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'objet de jeu a été créé');

            return $this->redirectToRoute('item.index', [], 303);
        }

        return $this->render('objet/link.twig', [
            'item' => $item,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Création d'un nouvel objet de jeu.
     */
    #[Route('/new/{objet}', name: 'new')]
    public function newAction(
        Request $request,
        #[MapEntity] Objet $objet,
    ): RedirectResponseAlias|Response {
        $item = new Item();
        $item->setObjet($objet);

        $form = $this->createForm(ItemForm::class, $item);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $item = $form->getData();

            // si le numéro est vide, générer un numéro en suivant l'ordre
            $numero = $item->getNumero();
            if (!$numero) {
                $repo = $this->entityManager->getRepository('\\'.Item::class);
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
                    $identification = sprintf('%02d', random_int(1, 10));
                    $item->setIdentification($identification);
                    break;
                case 11:
                    $identification = random_int(11, 20);
                    $item->setIdentification($identification);
                    break;
                case 81:
                    $identification = random_int(81, 99);
                    $item->setIdentification($identification);
                    break;
            }

            $this->entityManager->persist($item);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'objet de jeu a été créé');

            return $this->redirectToRoute('item.index', [], 303);
        }

        return $this->render('objet/new.twig', [
            'objet' => $objet,
            'item' => $item,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Impression d'une etiquette.
     */
    #[Route('/{item}/print', name: 'print')]
    public function printAction(#[MapEntity] Item $item): Response
    {
        return $this->render('objet/print.twig', [
            'item' => $item,
        ]);
    }

    /**
     * Impression de toutes les etiquettes.
     */
    #[Route('/print-all', name: 'print-all')]
    public function printAllAction(ItemRepository $itemRepository): Response
    {
        return $this->render('objet/printAll.twig', [
            'items' => $itemRepository->findAll(),
        ]);
    }


    #[Route('/print-csv', name: 'print-csv')]
    public function printCsvAction(ItemRepository $itemRepository): StreamedResponse
    {
        return $this->sendCsv(
            'eveoniris_game_item_'.date('Ymd'),
            repository: $itemRepository,
        );
    }

    /**
     * Impression de tous les objets avec photo.
     */
    #[Route('/print-photo', name: 'print-photo')]
    public function printPhotoAction(ItemRepository $itemRepository): Response
    {
        return $this->render('objet/printPhoto.twig', [
            'items' => $itemRepository->findAll(),
        ]);
    }

    /**
     * Mise à jour d'un objet de jeu.
     */
    #[Route('/{item}/update', name: 'update')]
    public function updateAction(
        Request $request,
        #[MapEntity] Item $item,
    ): RedirectResponseAlias|Response {
        $form = $this->createForm(ItemForm::class, $item);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($item);
            $this->entityManager->flush();

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

            return $this->redirectToRoute('item.index', [], 303);
        }

        return $this->render('objet/update.twig', [
            'item' => $item,
            'form' => $form->createView(),
        ]);
    }
}
