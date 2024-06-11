<?php

namespace App\Controller;

use App\Entity\Religion;
use App\Entity\ReligionLevel;
use App\Form\Religion\ReligionBlasonForm;
use App\Form\Religion\ReligionDeleteForm;
use App\Form\Religion\ReligionForm;
use App\Form\Religion\ReligionLevelForm;
use App\Repository\ReligionRepository;
use App\Repository\TopicRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReligionController extends AbstractController
{
    /**
     * Liste des perso ayant cette religion.
     */
    #[Route('/religion/{religion}/perso', name: 'religion.perso')]
    public function persoAction(Request $request, Religion $religion): Response
    {
        return $this->render(
            'admin/religion/perso.twig',
            [
                'religion' => $religion,
            ]
        );
    }

    /**
     * affiche la liste des religions.
     */
    #[Route('/religion', name: 'religion.index')]
    public function indexAction(Request $request, ReligionRepository $religionRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $orderBy = $request->query->getString('order_by', 'label');
        $orderDir = $request->query->getString('order_dir', 'ASC');
        $limit = 10;

        if ($this->isGranted('ROLE_REGLE')) {
            $where = '1=1';
        } else {
            $where = 'religion.secret = 0';
        }

        $paginator = $religionRepository->findPaginated($page, $limit, $orderBy, $orderDir, $where);

        return $this->render(
            'religion\list.twig',
            [
                'paginator' => $paginator,
                'limit' => $limit,
                'page' => $page,
            ]
        );
    }

    /**
     * affiche la liste des religions.
     */
    #[Route('/religion/mail', name: 'religion.mail')]
    public function mailAction(Request $request, ReligionRepository $religionRepository): Response
    {
        $religions = $religionRepository->findAllOrderedByLabel();

        return $this->render(
            'admin/religion/mail.twig',
            [
                'religions' => $religions,
            ]
        );
    }

    /**
     * Detail d'une religion.
     */
    #[Route('/religion/{religion}/detail', name: 'religion.detail')]
    public function detailAction(Religion $religion): Response
    {
        return $this->render(
            'admin/religion/detail.twig',
            [
                'religion' => $religion,
            ]
        );
    }

    /**
     * Ajoute une religion.
     */
    #[Route('/religion/add', name: 'religion.add')]
    public function addAction(EntityManagerInterface $entityManager, Request $request, TopicRepository $topicRepository): Response
    {
        $religion = new Religion();

        $form = $this->createForm(ReligionForm::class, $religion)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder & continuer'])
        ;

        $form->handleRequest($request);

        // si l'utilisateur soumet une nouvelle religion
        if ($form->isSubmitted() && $form->isValid()) {
            $religion = $form->getData();

            /**
             * Création du topic associés à cette religion
             * Ce topic doit être placé dans le topic "culte".
             *
             * @var \App\Entity\Topic $topic
             */
            $topic = new \App\Entity\Topic();
            $topic->setTitle($religion->getLabel());
            $topic->setDescription($religion->getDescription());
            $topic->setUser($this->getUser());
            $topic->setTopic($topicRepository->findOneBy(['key' => 'TOPIC_CULTE']));
            $topic->setRight('CULTE');

            $entityManager->persist($topic);
            $entityManager->flush();

            $religion->setTopic($topic);
            $entityManager->persist($religion);
            $entityManager->flush();

            $topic->setObjectId($religion->getId());
            $entityManager->flush();

            $this->addFlash('success', 'La religion a été ajoutée.');

            // l'utilisateur est redirigé soit vers la liste des religions, soit vers de nouveau
            // vers le formulaire d'ajout d'une religion
            if ($form->get('save')->isClicked()) {
                // return $this->redirectToRoute('religion.index', [], 303);
                return $this->redirectToRoute('religion.index', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                // return $this->redirectToRoute('religion.add', [], 303);
                return $this->redirectToRoute('religion.add', [], 303);
            }
        }

        return $this->render(
            'admin/religion/add.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Modifie une religion. Si l'utilisateur clique sur "sauvegarder", la religion est sauvegardée et
     * l'utilisateur est redirigé vers la liste des religions.
     * Si l'utilisateur clique sur "supprimer", la religion est supprimée et l'utilisateur est
     * redirigé vers la liste des religions.
     */
    #[Route('/religion/{religion}/update', name: 'religion.update')]
    public function updateAction(EntityManagerInterface $entityManager, Request $request, Religion $religion)
    {
        $form = $this->createForm(ReligionForm::class, $religion)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer'])
        ;

        $originalSpheres = new ArrayCollection();
        foreach ($religion->getSpheres() as $sphere) {
            $originalSpheres->add($sphere);
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $religion = $form->getData();

            if ($form->get('update')->isClicked()) {
                foreach ($religion->getSpheres() as $sphere) {
                    if (false == $sphere->getReligions()->contains($religion)) {
                        $sphere->addReligion($religion);
                    }
                }
                foreach ($originalSpheres as $sphere) {
                    if (false == $religion->getspheres()->contains($sphere)) {
                        $sphere->removeReligion($religion);
                    }
                }
                $entityManager->persist($religion);
                $entityManager->flush();
                $this->addFlash('success', 'La religion a été mise à jour.');

                return $this->redirectToRoute('religion.detail', ['religion' => $religion->getId()], 303);
            // return $this->redirectToRoute('religion.detail', [], 303);
            } elseif ($form->get('delete')->isClicked()) {
                /*$entityManager->remove($religion);
                $entityManager->flush();
                $this->addFlash('success', 'La religion a été supprimée.');*/
                // return $this->redirectToRoute('religion.index', [], 303);
                return $this->redirectToRoute('religion.delete', ['religion' => $religion->getId()], 303);
            }
        }

        return $this->render(
            'admin/religion/update.twig',
            [
                'religion' => $religion,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Supression d'une religion.
     */
    #[Route('/religion/{religion}/delete', name: 'religion.delete')]
    public function deleteAction(Request $request, EntityManagerInterface $entityManager, Religion $religion)
    {
        $form = $this->createForm(ReligionDeleteForm::class, $religion)
            ->add('delete', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $religion = $form->getData();

            foreach ($religion->getPersonnages() as $personnage) {
                $personnage->removeReligion($religion);
                $entityManager->persist($personnage);
            }

            foreach ($religion->getTerritoires() as $territoire) {
                if ($territoire->getReligion() == $religion) {
                    $territoire->setReligion(null);
                }
                $territoire->removeReligion($religion);
                $entityManager->persist($territoire);
            }

            $entityManager->remove($religion);
            $entityManager->flush();
            $this->addFlash('success', 'La religion a été supprimée.');

            return $this->redirectToRoute('religion.index', [], 303);
        }

        return $this->render('religion/delete.twig', [
            'religion' => $religion,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Récupération de l'image du blason d'une religion.
     */
    #[Route('/religion/{religion}/blason', name: 'religion.blason', methods: ['GET'])]
    public function blasonAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Religion $religion): Response
    {
        $blason = $religion->getBlason();
        $filename = __DIR__.'/../../assets/img/religions/'.$blason;

        $response = new Response(file_get_contents($filename));
        $response->headers->set('Content-Type', 'image/png');

        return $response;
    }

    /**
     * Met à jour le blason d'une religion.
     */
    #[Route('/religion/{religion}/updateBlason', name: 'religion.update.blason')]
    public function updateBlasonAction(Request $request, EntityManagerInterface $entityManager, Religion $religion)
    {
        $form = $this->createForm(ReligionBlasonForm::class, $religion)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $files = $request->files->get($form->getName());

            $path = __DIR__.'/../../assets/img/blasons/';
            $filename = $files['blason']->getClientOriginalName();
            $extension = $files['blason']->guessExtension();

            if (!$extension || !in_array($extension, ['png', 'jpg', 'jpeg', 'bmp'])) {
                $this->addFlash('error', 'Désolé, votre image ne semble pas valide (vérifiez le format de votre image)');

                return $this->redirectToRoute('religion.detail', ['religion' => $religion->getId()], 303);
            }

            $blasonFilename = hash('md5', $this->getUser()->getUsername().$filename.time()).'.'.$extension;

            $imagine = new \Imagine\Gd\Imagine();
            $image = $imagine->open($files['blason']->getPathname());
            $image->resize($image->getSize()->widen(160));
            $image->save($path.$blasonFilename);

            $religion->setBlason($blasonFilename);
            $entityManager->persist($religion);
            $entityManager->flush();

            $this->addFlash('success', 'Le blason a été enregistré');

            return $this->redirectToRoute('religion.detail', ['religion' => $religion->getId()], 303);
        }

        return $this->render('religion/blason.twig', [
            'religion' => $religion,
            'form' => $form->createView(),
        ]);
    }

    /**
     * affiche la liste des niveaux de fanatisme.
     */
    #[Route('/religion/level', name: 'religion.level')]
    public function levelIndexAction(Request $request, EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\\'.ReligionLevel::class);
        $religionLevels = $repo->findAllOrderedByIndex();

        return $this->render('religion/level/index.twig', ['religionLevels' => $religionLevels]);
    }

    /**
     * Detail d'un niveau de fanatisme.
     */
    #[Route('/religion/level/{religionLevel}/detail', name: 'religion.level.detail')]
    public function levelDetailAction(Request $request, EntityManagerInterface $entityManager, ReligionLevel $religionLevel)
    {
        return $this->render('religion/level/detail.twig', ['religionLevel' => $religionLevel]);
    }

    /**
     * Ajoute un niveau de fanatisme.
     */
    #[Route('/religion/level/add', name: 'religion.level.add')]
    public function levelAddAction(Request $request, EntityManagerInterface $entityManager)
    {
        $religionLevel = new ReligionLevel();

        $form = $this->createForm(ReligionLevelForm::class, $religionLevel)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        // si l'utilisateur soumet une nouvelle religion
        if ($form->isSubmitted() && $form->isValid()) {
            $religionLevel = $form->getData();

            $entityManager->persist($religionLevel);
            $entityManager->flush();

            $this->addFlash('success', 'Le niveau de religion a été ajoutée.');

            // l'utilisateur est redirigé soit vers la liste des niveaux de religions, soit vers de nouveau
            // vers le formulaire d'ajout d'un niveau de religion
            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('religion.level', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('religion.level.add', [], 303);
            }
        }

        return $this->render('religion/level/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie un niveau de religion. Si l'utilisateur clique sur "sauvegarder", le niveau de religion est sauvegardée et
     * l'utilisateur est redirigé vers la liste des niveaux de religions.
     * Si l'utilisateur clique sur "supprimer", le niveau religion est supprimée et l'utilisateur est
     * redirigé vers la liste de niveaux de religions.
     */
    #[Route('/religion/level/{religionLevel}/update', name: 'religion.level.update')]
    public function levelUpdateAction(Request $request, EntityManagerInterface $entityManager, ReligionLevel $religionLevel)
    {
        $form = $this->createForm(ReligionLevelForm::class, $religionLevel)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $religionLevel = $form->getData();

            if ($form->get('update')->isClicked()) {
                $entityManager->persist($religionLevel);
                $entityManager->flush();
                $this->addFlash('success', 'Le niveau de religion a été mise à jour.');

                return $this->redirectToRoute('religion.level.detail', ['religionLevel' => $religionLevel->getId()], 303);
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($religionLevel);
                $entityManager->flush();
                $this->addFlash('success', 'Le niveau de religion a été supprimée.');

                return $this->redirectToRoute('religion.level', [], 303);
            }
        }

        return $this->render('religion/level/update.twig', [
            'religionLevel' => $religionLevel,
            'form' => $form->createView(),
        ]);
    }
}
