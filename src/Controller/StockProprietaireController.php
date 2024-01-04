<?php

namespace App\Controller;

use LarpManager\Form\Type\ProprietaireType;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_STOCK')]
class StockProprietaireController extends AbstractController
{
    #[Route('/stock/proprietaire', name: 'stockProprietaire.index')]
    public function indexAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Proprietaire::class);
        $proprietaires = $repo->findAll();

        return $app['twig']->render('stock/proprietaire/index.twig', ['proprietaires' => $proprietaires]);
    }

    public function addAction(Request $request, Application $app)
    {
        $proprietaire = new \App\Entity\Proprietaire();

        $form = $app['form.factory']->createBuilder(new ProprietaireType(), $proprietaire)
            ->add('save', 'submit')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $proprietaire = $form->getData();
            $app['orm.em']->persist($proprietaire);
            $app['orm.em']->flush();

           $this->addFlash('success', 'Le propriétaire a été ajouté');

            return $this->redirectToRoute('stock_proprietaire_index');
        }

        return $app['twig']->render('stock/proprietaire/add.twig', ['form' => $form->createView()]);
    }

    public function updateAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Proprietaire::class);
        $proprietaire = $repo->find($id);

        $form = $app['form.factory']->createBuilder(new ProprietaireType(), $proprietaire)
            ->add('update', 'submit')
            ->add('delete', 'submit')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            // on récupére les data de l'utilisateur
            $proprietaire = $form->getData();

            if ($form->get('update')->isClicked()) {
                $app['orm.em']->persist($proprietaire);
                $app['orm.em']->flush();
               $this->addFlash('success', 'Le propriétaire a été mis à jour');
            } elseif ($form->get('delete')->isClicked()) {
                $app['orm.em']->remove($proprietaire);
                $app['orm.em']->flush();
               $this->addFlash('success', 'Le proprietaire a été supprimé');
            }

            return $this->redirectToRoute('stock_proprietaire_index');
        }

        return $app['twig']->render('stock/proprietaire/update.twig', ['proprietaire' => $proprietaire, 'form' => $form->createView()]);
    }
}
