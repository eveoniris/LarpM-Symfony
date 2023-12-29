<?php

namespace App\Controller;

use App\Entity\Billet;
use App\Repository\BilletRepository;
use LarpManager\Form\BilletDeleteForm;
use LarpManager\Form\BilletForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BilletController extends AbstractController
{
    /**
     * Liste des billets.
     */
    #[Route('/billet/list', name: 'billet.list')]
    public function listAction(Request $request, BilletRepository $billetRepository): Response
    {
        $orderBy = $this->getRequestOrder(
            alias: 'b',
            allowedFields: $billetRepository->getFieldNames()
        );

        $query = $billetRepository->createQueryBuilder('b')
            ->orderBy(key($orderBy), current($orderBy));

        $paginator = $billetRepository->findPaginatedQuery(
            $query->getQuery(), $this->getRequestLimit(), $this->getRequestPage()
        );

        return $this->render(
            'billet\list.twig', ['paginator' => $paginator]
        );
    }

    /**
     * Ajout d'un billet.
     */
    #[Route('/billet/add', name: 'billet.add')]
    public function addAction(Request $request, Application $app)
    {
        $billet = new Billet();
        $gnId = $request->get('gn');

        if ($gnId) {
            $gn = $app['orm.em']->getRepository(\App\Entity\Gn::class)->find($gnId);
            $billet->setGn($gn);
        }

        $form = $app['form.factory']->createBuilder(new BilletForm(), $billet)
            ->add('submit', 'submit', ['label' => 'Valider'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $billet = $form->getData();
            $billet->setCreateur($this->getUser());
            $app['orm.em']->persist($billet);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le billet a été ajouté.');

            return $app->redirect($app['url_generator']->generate('billet.list'), 303);
        }

        return $app['twig']->render('admin\billet\add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'un billet.
     */
    #[Route('/billet/detail', name: 'billet.detail')]
    public function detailAction(Request $request, Application $app, Billet $billet)
    {
        return $app['twig']->render('admin\billet\detail.twig', [
            'billet' => $billet,
        ]);
    }

    /**
     * Mise à jour d'un billet.
     */
    #[Route('/billet/update', name: 'billet.update')]
    public function updateAction(Request $request, Application $app, Billet $billet)
    {
        $form = $app['form.factory']->createBuilder(new BilletForm(), $billet)
            ->add('submit', 'submit', ['label' => 'Valider'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $billet = $form->getData();
            $app['orm.em']->persist($billet);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le billet a été mis à jour.');

            return $app->redirect($app['url_generator']->generate('billet.list'), 303);
        }

        return $app['twig']->render('admin\billet\update.twig', [
            'form' => $form->createView(),
            'billet' => $billet,
        ]);
    }

    /**
     * Suppression d'un billet.
     */
    #[Route('/billet/delete', name: 'billet.delete')]
    public function deleteAction(Request $request, Application $app, Billet $billet)
    {
        $form = $app['form.factory']->createBuilder(new BilletDeleteForm(), $billet)
            ->add('submit', 'submit', ['label' => 'Valider'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $billet = $form->getData();
            $app['orm.em']->remove($billet);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le billet a été supprimé.');

            return $app->redirect($app['url_generator']->generate('billet.list'), 303);
        }

        return $app['twig']->render('admin\billet\delete.twig', [
            'form' => $form->createView(),
            'billet' => $billet,
        ]);
    }

    /**
     * Liste des utilisateurs ayant ce billet.
     */
    public function participantsAction(Request $request, Application $app, Billet $billet)
    {
        return $app['twig']->render('admin\billet\participants.twig', [
            'billet' => $billet,
        ]);
    }
}
