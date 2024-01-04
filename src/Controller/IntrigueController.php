<?php


namespace App\Controller;

use App\Entity\Intrigue;
use App\Entity\IntrigueHasModification;
use App\Entity\Relecture;
use Doctrine\Common\Collections\ArrayCollection;
use JasonGrimes\Paginator;
use App\Form\Intrigue\IntrigueDeleteForm;
use App\Form\Intrigue\IntrigueFindForm;
use App\Form\Intrigue\IntrigueForm;
use App\Form\Intrigue\IntrigueRelectureForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_SCENARISTE')]
class IntrigueController extends AbstractController
{
    /**
     * Liste de toutes les intrigues.
     */
    #[Route('/intrigue', name: 'intrigue.list')]
    public function listAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $order_by = $request->get('order_by') ?: 'titre';
        $order_dir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $type = null;
        $value = null;

        $form = $this->createForm(new IntrigueFindForm())->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['search'];
        }

        $repo = $entityManager->getRepository('\\'.\App\Entity\Intrigue::class);

        $intrigues = $repo->findList(
            $type,
            $value,
            ['by' => $order_by, 'dir' => $order_dir],
            $limit,
            $offset);

        $numResults = $repo->findCount($type, $value);

        $paginator = new Paginator($numResults, $limit, $page,
            $app['url_generator']->generate('intrigue.list').'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir
        );

        return $this->render('admin/intrigue/list.twig', [
            'form' => $form->createView(),
            'intrigues' => $intrigues,
            'paginator' => $paginator,
        ]);
    }

    /**
     * Lire une intrigue.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager, Intrigue $intrigue)
    {
        return $this->render('admin/intrigue/detail.twig', [
            'intrigue' => $intrigue,
        ]);
    }

    /**
     * Ajouter une intrigue.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $intrigue = new Intrigue();
        $form = $this->createForm(IntrigueForm::class(), $intrigue)
            ->add('state', 'choice', [
                'required' => true,
                'label' => 'Etat',
                'choices' => $app['larp.manager']->getState(),
            ])
            ->add('add', 'submit', ['label' => "Ajouter l'intrigue"])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $intrigue = $form->getData();

            if (!$intrigue->getDescription()) {
               $this->addFlash('error', 'la description de votre intrigue est obligatoire.');
            } elseif (!$intrigue->getText()) {
               $this->addFlash('error', 'le texte de votre intrigue est obligatoire.');
            } else {
                $intrigue->setUser($this->getUser());

                /*
                 * Pour tous les groupes de l'intrigue
                 */
                foreach ($intrigue->getIntrigueHasGroupes() as $intrigueHasGroupe) {
                    $intrigueHasGroupe->setIntrigue($intrigue);
                }

                /*
                 * Pour tous les groupes secondaires de l'intrigue
                 */
                foreach ($intrigue->getIntrigueHasGroupeSecondaires() as $intrigueHasGroupeSecondaire) {
                    $intrigueHasGroupeSecondaire->setIntrigue($intrigue);
                }

                /*
                 * Pour tous les documents de l'intrigue
                 */
                foreach ($intrigue->getIntrigueHasDocuments() as $intrigueHasDocument) {
                    $intrigueHasDocument->setIntrigue($intrigue);
                }

                /*
                 * Pour tous les lieux de l'intrigue
                 */
                foreach ($intrigue->getIntrigueHasLieus() as $intrigueHasLieu) {
                    $intrigueHasLieu->setIntrigue($intrigue);
                }

                /*
                 * Pour tous les événements de l'intrigue
                 */
                foreach ($intrigue->getIntrigueHasEvenements() as $intrigueHasEvenement) {
                    $intrigueHasEvenement->setIntrigue($intrigue);
                }

                /*
                 * Pour tous les objectifs de l'intrigue
                 */
                foreach ($intrigue->getIntrigueHasObjectifs() as $intrigueHasObjectif) {
                    $intrigueHasObjectif->setIntrigue($intrigue);
                }

                $entityManager->persist($intrigue);
                $entityManager->flush();

                /*
                 * Envoyer une notification à tous les scénaristes des groupes concernés (hors utilisateur courant)
                 */
                foreach ($intrigue->getIntrigueHasGroupes() as $intrigueHasGroupe) {
                    if (false == $this->getUser()->getGroupeScenariste()->contains($intrigueHasGroupe->getGroupe())) {
                        $app['notify']->intrigue($intrigue, $intrigueHasGroupe->getGroupe());
                    }
                }

               $this->addFlash('success', 'Votre intrigue a été ajouté.');

                return $this->redirectToRoute('intrigue.list', [], 303);
            }
        }

        return $this->render('admin/intrigue/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mettre à jour une intrigue.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, Intrigue $intrigue)
    {
        $originalIntrigueHasGroupes = new ArrayCollection();
        $originalIntrigueHasGroupeSecondaires = new ArrayCollection();
        $originalIntrigueHasEvenements = new ArrayCollection();
        $originalIntrigueHasObjectifs = new ArrayCollection();
        $originalIntrigueHasDocuments = new ArrayCollection();
        $originalIntrigueHasLieus = new ArrayCollection();

        /*
         *  Crée un tableau contenant les objets IntrigueHasGroupe de l'intrigue
         */
        foreach ($intrigue->getIntrigueHasGroupes() as $intrigueHasGroupe) {
            $originalIntrigueHasGroupes->add($intrigueHasGroupe);
        }

        /*
         *  Crée un tableau contenant les objets IntrigueHasGroupeSecondaire de l'intrigue
         */
        foreach ($intrigue->getIntrigueHasGroupeSecondaires() as $intrigueHasGroupeSecondaire) {
            $originalIntrigueHasGroupeSecondaires->add($intrigueHasGroupeSecondaire);
        }

        /*
         *  Crée un tableau contenant les objets IntrigueHasEvenement de l'intrigue
         */
        foreach ($intrigue->getIntrigueHasEvenements() as $intrigueHasEvenement) {
            $originalIntrigueHasEvenements->add($intrigueHasEvenement);
        }

        /*
         *  Crée un tableau contenant les objets IntrigueHasObjectif de l'intrigue
         */
        foreach ($intrigue->getIntrigueHasObjectifs() as $intrigueHasObjectif) {
            $originalIntrigueHasObjectifs->add($intrigueHasObjectif);
        }

        /*
         *  Crée un tableau contenant les objets IntrigueHasDocument de l'intrigue
         */
        foreach ($intrigue->getIntrigueHasDocuments() as $intrigueHasDocument) {
            $originalIntrigueHasDocuments->add($intrigueHasDocument);
        }

        /*
         *  Crée un tableau contenant les objets IntrigueHasLieu de l'intrigue
         */
        foreach ($intrigue->getIntrigueHasLieus() as $intrigueHasLieu) {
            $originalIntrigueHasLieus->add($intrigueHasLieu);
        }

        $form = $this->createForm(IntrigueForm::class(), $intrigue)
            ->add('state', 'choice', [
                'required' => true,
                'label' => 'Etat',
                'choices' => $app['larp.manager']->getState(),
            ])
            ->add('enregistrer', 'submit', ['label' => 'Enregistrer'])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $intrigue = $form->getData();
            $intrigue->setDateUpdate(new \DateTime('NOW'));

            /*
             * Pour tous les groupes de l'intrigue
             */
            foreach ($intrigue->getIntrigueHasGroupes() as $intrigueHasGroupe) {
                $intrigueHasGroupe->setIntrigue($intrigue);
            }

            /*
             * Pour tous les groupes secondaires de l'intrigue
             */
            foreach ($intrigue->getIntrigueHasGroupeSecondaires() as $intrigueHasGroupeSecondaire) {
                $intrigueHasGroupeSecondaire->setIntrigue($intrigue);
            }

            /*
             * Pour tous les événements de l'intrigue
             */
            foreach ($intrigue->getIntrigueHasEvenements() as $intrigueHasEvenement) {
                $intrigueHasEvenement->setIntrigue($intrigue);
            }

            /*
             * Pour tous les objectifs de l'intrigue
             */
            foreach ($intrigue->getIntrigueHasObjectifs() as $intrigueHasObjectif) {
                $intrigueHasObjectif->setIntrigue($intrigue);
            }

            /*
             * Pour tous les documents de l'intrigue
             */
            foreach ($intrigue->getIntrigueHasDocuments() as $intrigueHasDocument) {
                $intrigueHasDocument->setIntrigue($intrigue);
            }

            /*
             * Pour tous les lieus de l'intrigue
             */
            foreach ($intrigue->getIntrigueHasLieus() as $intrigueHasLieu) {
                $intrigueHasLieu->setIntrigue($intrigue);
            }

            /*
             *  supprime la relation entre intrigueHasGroupe et l'intrigue
             */
            foreach ($originalIntrigueHasGroupes as $intrigueHasGroupe) {
                if (false == $intrigue->getIntrigueHasGroupes()->contains($intrigueHasGroupe)) {
                    $entityManager->remove($intrigueHasGroupe);
                }
            }

            /*
             *  supprime la relation entre intrigueHasGroupe et l'intrigue
             */
            foreach ($originalIntrigueHasGroupeSecondaires as $intrigueHasGroupeSecondaire) {
                if (false == $intrigue->getIntrigueHasGroupes()->contains($intrigueHasGroupeSecondaire)) {
                    $entityManager->remove($intrigueHasGroupeSecondaire);
                }
            }

            /*
             *  supprime la relation entre intrigueHasEvenement et l'intrigue
             */
            foreach ($originalIntrigueHasEvenements as $intrigueHasEvenement) {
                if (false == $intrigue->getIntrigueHasEvenements()->contains($intrigueHasEvenement)) {
                    $entityManager->remove($intrigueHasEvenement);
                }
            }

            /*
             *  supprime la relation entre intrigueHasObjectif et l'intrigue
             */
            foreach ($originalIntrigueHasObjectifs as $intrigueHasObjectif) {
                if (false == $intrigue->getIntrigueHasObjectifs()->contains($intrigueHasObjectif)) {
                    $entityManager->remove($intrigueHasObjectif);
                }
            }

            /*
             *  supprime la relation entre intrigueHasDocument et l'intrigue
             */
            foreach ($originalIntrigueHasDocuments as $intrigueHasDocument) {
                if (false == $intrigue->getIntrigueHasDocuments()->contains($intrigueHasDocument)) {
                    $entityManager->remove($intrigueHasDocument);
                }
            }

            /*
             *  supprime la relation entre intrigueHasLieu et l'intrigue
             */
            foreach ($originalIntrigueHasLieus as $intrigueHasLieu) {
                if (false == $intrigue->getIntrigueHasLieus()->contains($intrigueHasLieu)) {
                    $entityManager->remove($intrigueHasLieu);
                }
            }

            /**
             * Création d'une ligne dans la liste des modifications de l'intrigue.
             */
            $modification = new IntrigueHasModification();
            $modification->setUser($this->getUser());
            $modification->setIntrigue($intrigue);
            $entityManager->persist($modification);
            $entityManager->persist($intrigue);
            $entityManager->flush();

            /*
             * Envoyer une notification à tous les scénaristes des groupes concernés (hors utilisateur courant)
             */
            foreach ($intrigue->getIntrigueHasGroupes() as $intrigueHasGroupe) {
                if (false == $this->getUser()->getGroupeScenariste()->contains($intrigueHasGroupe->getGroupe())) {
                    $app['notify']->intrigue($intrigue, $intrigueHasGroupe->getGroupe());
                }
            }

            /*
             * Envoyer une notification à tous les utilisateurs ayant préalablement modifier cette intrigue (hors utilisateur courant, et hors scénariste d'un groupe concerné)
             */
            foreach ($intrigue->getIntrigueHasModifications() as $modification) {
                if ($modification->getUser() != $this->getUser()) {
                    $sendNotification = true;
                    foreach ($intrigue->getIntrigueHasGroupes() as $intrigueHasGroupe) {
                        if (true == $modification->getUser()->getGroupeScenariste()->contains($intrigueHasGroupe->getGroupe())) {
                            $sendNotification = false;
                        }
                    }

                    if ($sendNotification) {
                        $app['notify']->intrigue($intrigue, $intrigueHasGroupe->getGroupe());
                    }
                }
            }

           $this->addFlash('success', 'Votre intrigue a été modifiée.');

            return $this->redirectToRoute('intrigue.detail', ['intrigue' => $intrigue->getId()], [], 303);
        }

        return $this->render('admin/intrigue/update.twig', [
            'form' => $form->createView(),
            'intrigue' => $intrigue,
        ]);
    }

    /**
     * Supression d'une intrigue.
     */
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager, Intrigue $intrigue)
    {
        $form = $this->createForm(IntrigueDeleteForm::class(), $intrigue)
            ->add('supprimer', 'submit', ['label' => 'Supprimer'])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $intrigue = $form->getData();
            $entityManager->remove($intrigue);
            $entityManager->flush();

           $this->addFlash('success', 'L\'intrigue a été supprimée.');

            return $this->redirectToRoute('intrigue.list', [], 303);
        }

        return $this->render('admin/intrigue/delete.twig', [
            'form' => $form->createView(),
            'intrigue' => $intrigue,
        ]);
    }

    /**
     * Ajout d'une relecture.
     */
    public function relectureAddAction(Request $request,  EntityManagerInterface $entityManager, Intrigue $intrigue)
    {
        $relecture = new Relecture();
        $form = $this->createForm(IntrigueRelectureForm::class(), $relecture)
            ->add('enregistrer', 'submit', ['label' => 'Enregistrer'])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $relecture = $form->getData();
            $relecture->setUser($this->getUser());
            $relecture->setIntrigue($intrigue);

            $entityManager->persist($relecture);
            $entityManager->flush();

            /*
             * Envoyer une notification à tous les scénaristes des groupes concernés (hors utilisateur courant)
             */
            foreach ($intrigue->getIntrigueHasGroupes() as $intrigueHasGroupe) {
                if (false == $this->getUser()->getGroupeScenariste()->contains($intrigueHasGroupe->getGroupe())) {
                    $app['notify']->relecture($intrigue, $intrigueHasGroupe->getGroupe());
                }
            }

           $this->addFlash('success', 'Votre relecture a été enregistrée.');

            return $this->redirectToRoute('intrigue.detail', ['intrigue' => $intrigue->getId()], [], 303);
        }

        return $this->render('admin/intrigue/relecture.twig', [
            'form' => $form->createView(),
            'intrigue' => $intrigue,
        ]);
    }
}
