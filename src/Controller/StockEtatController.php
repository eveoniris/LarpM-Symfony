<?php

namespace App\Controller;

use LarpManager\Form\Type\EtatType;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_STOCK')]
class StockEtatController extends AbstractController
{
    #[Route('/stock/etat', name: 'stockEtat.index')]
    public function indexAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Etat::class);
        $etats = $repo->findAll();

        return $app['twig']->render('stock/etat/index.twig', ['etats' => $etats]);
    }

    public function addAction(Request $request, Application $app)
    {
        $etat = new \App\Entity\Etat();

        $form = $app['form.factory']->createBuilder(new EtatType(), $etat)
            ->add('save', 'submit')
            ->getForm();

        // on passe la requête de l'utilisateur au formulaire
        $form->handleRequest($request);

        // si la requête est valide
        if ($form->isValid()) {
            // on récupére les data de l'utilisateur
            $etat = $form->getData();
            $app['orm.em']->persist($etat);
            $app['orm.em']->flush();

            $this->addFlash('success', 'L\'état a été ajouté.');

            return $this->redirectToRoute('stock_etat_index');
        }

        return $app['twig']->render('stock/etat/add.twig', ['form' => $form->createView()]);
    }

    public function updateAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Etat::class);
        $etat = $repo->find($id);

        $form = $app['form.factory']->createBuilder(new EtatType(), $etat)
            ->add('update', 'submit')
            ->add('delete', 'submit')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $etat = $form->getData();

            if ($form->get('update')->isClicked()) {
                $app['orm.em']->persist($etat);
                $app['orm.em']->flush();
                $this->addFlash('success', 'L\'état a été mis à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $app['orm.em']->remove($etat);
                $app['orm.em']->flush();
                $this->addFlash('success', 'L\'état a été supprimé.');
            }

            return $this->redirectToRoute('stock_etat_index');
        }

        return $app['twig']->render('stock/etat/update.twig', [
            'etat' => $etat,
            'form' => $form->createView()]);
    }
}
