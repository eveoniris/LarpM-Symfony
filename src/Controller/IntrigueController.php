<?php


namespace App\Controller;

use App\Entity\Intrigue;
use App\Entity\IntrigueHasModification;
use App\Entity\Relecture;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use JasonGrimes\Paginator;
use App\Form\Intrigue\IntrigueDeleteForm;
use App\Form\Intrigue\IntrigueFindForm;
use App\Form\Intrigue\IntrigueForm;
use App\Form\Intrigue\IntrigueRelectureForm;
use App\Repository\IntrigueRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
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
    public function listAction(Request $request, EntityManagerInterface $entityManager, IntrigueRepository $intrigueRepository)
    {
        $type = null;
        $value = null;

        $form = $this->createForm(IntrigueFindForm::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['value'];
        }
        
        $alias = IntrigueRepository::getEntityAlias();
        dump($type);

        $criterias = [];
        if (!empty($value)) {
            if (empty($type) || '*' === $type) {
                $criterias[] = Criteria::create()->where(
                    Criteria::expr()?->contains($alias.'.titre', $value)
                )->orWhere(
                    Criteria::expr()?->contains($alias.'.description', $value)
                )->orWhere(
                    Criteria::expr()?->contains($alias.'.text', $value)
                )
                ;
            } else {
                $criterias[] = Criteria::create()->andWhere(
                    Criteria::expr()?->contains($alias.'.'.$type, $value)
                );
            }
        }

        $orderBy = $this->getRequestOrder(
            defOrderBy: 'titre',
            alias: $alias,
            allowedFields: $intrigueRepository->getFieldNames()
        );

        $paginator = $intrigueRepository->getPaginator(
            limit: $this->getRequestLimit(),
            page: $this->getRequestPage(),
            orderBy: $orderBy,
            alias: $alias,
            criterias: $criterias
        );

        return $this->render('intrigue/list.twig', [
            'form' => $form->createView(),
            //'intrigues' => $intrigues,
            'paginator' => $paginator,
        ]);
    }

    

    /**
     * Ajouter une intrigue.
     */
    #[Route('/intrigue/add', name: 'intrigue.add')]
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $intrigue = new Intrigue();
        $form = $this->createForm(IntrigueForm::class, $intrigue)
            ->add('state', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => true,
                'label' => 'Etat',
                'choices' => [
                    'L\'élément est actif' => 'ACTIF',
				    'L\'élément est inactif' => 'INACTIF',
                ],
            ])
            ->add('add', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => "Ajouter l'intrigue"]);

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
                        // NOTIFY $app['notify']->intrigue($intrigue, $intrigueHasGroupe->getGroupe());
                    }
                }

               $this->addFlash('success', 'Votre intrigue a été ajouté.');

                return $this->redirectToRoute('intrigue.list', [], 303);
            }
        }

        return $this->render('intrigue/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Lire une intrigue.
     */
    #[Route('/intrigue/{intrigue}', name: 'intrigue.detail')]
    public function detailAction(Request $request,  EntityManagerInterface $entityManager, Intrigue $intrigue)
    {
        return $this->render('intrigue/detail.twig', [
            'intrigue' => $intrigue,
        ]);
    }

    /**
     * Mettre à jour une intrigue.
     */
    #[Route('/intrigue/{intrigue}/update', name: 'intrigue.update')]
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

        $form = $this->createForm(IntrigueForm::class, $intrigue)
            ->add('state', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => true,
                'label' => 'Etat',
                'choices' => [
                    'L\'élément est actif' => 'ACTIF',
                    'L\'élément est inactif' => 'INACTIF',
                ],
            ])
            ->add('enregistrer', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Enregistrer']);

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
                    // NOTIFY $app['notify']->intrigue($intrigue, $intrigueHasGroupe->getGroupe());
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
                        // NOTIFY $app['notify']->intrigue($intrigue, $intrigueHasGroupe->getGroupe());
                    }
                }
            }

           $this->addFlash('success', 'Votre intrigue a été modifiée.');

            return $this->redirectToRoute('intrigue.detail', ['intrigue' => $intrigue->getId()], 303);
        }

        return $this->render('intrigue/update.twig', [
            'form' => $form->createView(),
            'intrigue' => $intrigue,
        ]);
    }

    /**
     * Supression d'une intrigue.
     */
    #[Route('/intrigue/{intrigue}/delete', name: 'intrigue.delete')]
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager, Intrigue $intrigue)
    {
        $form = $this->createForm(IntrigueDeleteForm::class, $intrigue)
            ->add('supprimer', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $intrigue = $form->getData();
            $entityManager->remove($intrigue);
            $entityManager->flush();

           $this->addFlash('success', 'L\'intrigue a été supprimée.');

            return $this->redirectToRoute('intrigue.list', [], 303);
        }

        return $this->render('intrigue/delete.twig', [
            'form' => $form->createView(),
            'intrigue' => $intrigue,
        ]);
    }

    /**
     * Ajout d'une relecture.
     */
    #[Route('/intrigue/{intrigue}/relecture', name: 'intrigue.relecture.add')]
    public function relectureAddAction(Request $request,  EntityManagerInterface $entityManager, Intrigue $intrigue)
    {
        $relecture = new Relecture();
        $form = $this->createForm(IntrigueRelectureForm::class, $relecture)
            ->add('enregistrer', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Enregistrer']);

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
                    // NOTIFY $app['notify']->relecture($intrigue, $intrigueHasGroupe->getGroupe());
                }
            }

           $this->addFlash('success', 'Votre relecture a été enregistrée.');

            return $this->redirectToRoute('intrigue.detail', ['intrigue' => $intrigue->getId()], 303);
        }

        return $this->render('intrigue/relecture.twig', [
            'form' => $form->createView(),
            'intrigue' => $intrigue,
        ]);
    }
}
