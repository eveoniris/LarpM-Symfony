<?php

namespace App\Controller;

use LarpManager\Form\Type\LocalisationType;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_STOCK')]
class StockLocalisationController extends AbstractController
{
    #[Route('/stock/localisation', name: 'stockLocalisation.index')]
    public function indexAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Localisation::class);
        $localisations = $repo->findAll();

        return $app['twig']->render('stock/localisation/index.twig', ['localisations' => $localisations]);
    }

    public function addAction(Request $request, Application $app)
    {
        $localisation = new \App\Entity\Localisation();

        $form = $app['form.factory']->createBuilder(new LocalisationType(), $localisation)
            ->add('save', 'submit')
            ->getForm();

        // on passe la requête de l'utilisateur au formulaire
        $form->handleRequest($request);

        // si la requête est valide
        if ($form->isValid()) {
            // on récupére les data de l'utilisateur
            $localisation = $form->getData();
            $app['orm.em']->persist($localisation);
            $app['orm.em']->flush();

           $this->addFlash('success', 'La localisation a été ajoutée.');

            return $this->redirectToRoute('stock_localisation_index');
        }

        return $app['twig']->render('stock/localisation/add.twig', ['form' => $form->createView()]);
    }

    public function updateAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Localisation::class);
        $localisation = $repo->find($id);

        $form = $app['form.factory']->createBuilder(new LocalisationType(), $localisation)
            ->add('update', 'submit')
            ->add('delete', 'submit')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $localisation = $form->getData();

            if ($form->get('update')->isClicked()) {
                $app['orm.em']->persist($localisation);
                $app['orm.em']->flush();
               $this->addFlash('success', 'La localisation a été mise à jour');
            } elseif ($form->get('delete')->isClicked()) {
                $app['orm.em']->remove($localisation);
                $app['orm.em']->flush();
               $this->addFlash('success', 'La localisation a été suprimée');
            }

            return $this->redirectToRoute('stock_localisation_index');
        }

        return $app['twig']->render('stock/localisation/update.twig', [
            'localisation' => $localisation,
            'form' => $form->createView()]);
    }
}
