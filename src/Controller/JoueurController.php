<?php

namespace App\Controller;

use App\Form\JoueurForm;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\JoueurController.
 *
 * @author kevin
 */
class JoueurController extends AbstractController
{
    /**
     * Affiche la vue index.twig.
     */
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\App\Entity\Joueur');
        $joueurs = $repo->findAll();

        return $this->render('joueur/index.twig', ['joueurs' => $joueurs]);
    }

    /**
     * Affiche le formulaire d'ajout d'un joueur.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $joueur = new \App\Entity\Joueur();

        $form = $this->createForm(JoueurForm::class, $joueur)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $joueur = $form->getData();
            $this->getUser()->setJoueur($joueur);

            $entityManager->persist($this->getUser());
            $entityManager->persist($joueur);
            $entityManager->flush();

           $this->addFlash('success', 'Vos informations ont été enregistrés.');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('joueur/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Detail d'un joueur.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $joueur = $entityManager->find('\App\Entity\Joueur', $id);

        if ($joueur) {
            return $this->render('joueur/detail.twig', ['joueur' => $joueur]);
        } else {
           $this->addFlash('error', 'Le joueur n\'a pas été trouvé.');

            return $this->redirectToRoute('joueur');
        }
    }

    /**
     * Met à jour les informations d'un joueur.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $joueur = $entityManager->find('\App\Entity\Joueur', $id);

        $form = $this->createForm(JoueurForm::class, $joueur)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $joueur = $form->getData();

            $entityManager->persist($joueur);
            $entityManager->flush();
           $this->addFlash('success', 'Le joueur a été mis à jour.');

            return $this->redirectToRoute('joueur.detail', ['index' => $id]);
        }

        return $this->render('joueur/update.twig', [
            'joueur' => $joueur,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met a jours les points d'expérience des joueurs.
     */
    public function xpAction( EntityManagerInterface $entityManager, Request $request)
    {
        $repo = $entityManager->getRepository('\App\Entity\Joueur');
        $joueurs = $repo->findAll();

        if ('POST' == $request->getMethod()) {
            $newXps = $request->get('xp');
            $explanation = $request->get('explanation');

            foreach ($joueurs as $joueur) {
                $personnage = $joueur->getPersonnage();
                if ($personnage->getXp() != $newXps[$joueur->getId()]) {
                    $oldXp = $personnage->getXp();
                    $gain = $newXps[$joueur->getId()] - $oldXp;

                    $personnage->setXp($newXps[$joueur->getId()]);
                    $entityManager->persist($personnage);

                    // historique
                    $historique = new \App\Entity\ExperienceGain();
                    $historique->setExplanation($explanation);
                    $historique->setOperationDate(new \DateTime('NOW'));
                    $historique->setPersonnage($personnage);
                    $historique->setXpGain($gain);
                    $entityManager->persist($historique);
                }
            }

            $entityManager->flush();

           $this->addFlash('success', 'Les points d\'expérience ont été sauvegardés');
        }

        return $this->render('joueur/xp.twig', [
            'joueurs' => $joueurs,
        ]);
    }
}
