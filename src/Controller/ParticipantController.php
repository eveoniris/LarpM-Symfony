<?php

namespace App\Controller;

use App\Entity\Competence;
use App\Entity\Connaissance;
use App\Entity\Document;
use App\Entity\Langue;
use App\Entity\Message;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\Postulant;
use App\Entity\Potion;
use App\Entity\Priere;
use App\Entity\Question;
use App\Entity\Reponse;
use App\Entity\Rule;
use App\Entity\SecondaryGroup;
use App\Entity\Sort;
use App\Entity\Technologie;
use App\Entity\User;
use App\Form\AcceptAllianceForm;
use App\Form\AcceptPeaceForm;
use App\Form\BreakAllianceForm;
use App\Form\CancelRequestedAllianceForm;
use App\Form\CancelRequestedPeaceForm;
use App\Form\DeclareWarForm;
use App\Form\FindJoueurForm;
use App\Form\Groupe\GroupeInscriptionForm;
use App\Form\Groupe\GroupeSecondairePostulerForm;
use App\Form\JoueurForm;
use App\Form\JoueurXpForm;
use App\Form\MessageForm;
use App\Form\Participant\ParticipantGroupeForm;
use App\Form\Participant\ParticipantNewForm;
use App\Form\Participant\ParticipantRemoveForm;
use App\Form\ParticipantBilletForm;
use App\Form\ParticipantPersonnageSecondaireForm;
use App\Form\ParticipantRestaurationForm;
use App\Form\Personnage\PersonnageEditForm;
use App\Form\Personnage\PersonnageForm;
use App\Form\Personnage\PersonnageOriginForm;
use App\Form\Personnage\PersonnageReligionForm;
use App\Form\RefuseAllianceForm;
use App\Form\RefusePeaceForm;
use App\Form\RequestAllianceForm;
use App\Form\RequestPeaceForm;
use App\Form\TrombineForm;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/**
 * LarpManager\Controllers\ParticipantController.
 *
 * @author kevin
 */
class ParticipantController extends AbstractController
{
    /**
     * Interface Joueur d'un jeu.
     */
    public function indexAction(EntityManagerInterface $entityManager, Request $request, Participant $participant)
    {
        $groupeGn = $participant->getSession();

        // liste des questions non répondu par le participant
        $repo = $entityManager->getRepository(Question::class);
        $questions = $repo->findByParticipant($participant);

        return $this->render('public/participant/index.twig', [
            'gn' => $participant->getGn(),
            'participant' => $participant,
            'groupeGn' => $groupeGn,
            'questions' => $questions,
        ]);
    }

    /**
     * Apporter une réponse à une question.
     *
     * @param unknown $reponse
     */
    public function reponseAction(EntityManagerInterface $entityManager, Request $request, Participant $participant, Question $question, $reponse)
    {
        $rep = new Reponse();
        $rep->setQuestion($question);
        $rep->setParticipant($participant);
        $rep->setReponse($reponse);

        $entityManager->persist($rep);
        $entityManager->flush();

        $this->addFlash('Success', 'Votre réponse a été prise en compte !');

        return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
    }

    /**
     * Supprimer une réponse à une question.
     */
    public function reponseDeleteAction(EntityManagerInterface $entityManager, Request $request, Participant $participant, Reponse $reponse)
    {
        $entityManager->remove($reponse);
        $entityManager->flush();

        $this->addFlash('Success', 'Votre réponse a été supprimée, veuillez répondre de nouveau à la question !');

        return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
    }

    /**
     * Création d'un nouveau participant.
     */
    #[Route('/participant/new', name: 'participant.new')]
    public function newAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $participant = new Participant();
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->find($request->get('user'));

        if ($user) {
            $participant->setUser($user);
        }


        $form = $this->createForm(ParticipantNewForm::class, $participant)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && null !== $participant->getUser()) {
            $participant = $form->getData();

            $entityManager->persist($participant);
            $entityManager->flush();

            $this->addFlash('success', 'Vos modifications ont été enregistrées.');

            return $this->redirectToRoute('user.admin.list', [], 303);
        }

        return $this->render('participant/new.twig', [
            'participant' => $participant,
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affecte un participant à un groupe.
     */
    #[Route('/participant/{participant}/groupe', name: 'participant.groupe')]
    public function groupeAction(EntityManagerInterface $entityManager, Request $request, #[MapEntity] Participant $participant)
    {
        // il faut un billet pour rejoindre un groupe
        /* Commenté parce que ça gène la manière de faire d'Edaelle.
        if ( ! $participant->getBillet() )
        {
           $this->addFlash('error','Désolé, le joueur doit obtenir un billet avant de pouvoir rejoindre un groupe');
            return $this->redirectToRoute('gn.detail', array('gn' => $participant->getGn()->getId())),303);
        }
        */
        $form = $this->createForm(ParticipantGroupeForm::class, $participant, ['gnId' => $participant->getGn()->getId()])
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $participant = $form->getData();
            $entityManager->persist($participant);
            $entityManager->flush();

            $this->addFlash('success', 'Vos modifications ont été enregistrées.');

            return $this->redirectToRoute('gn.participants', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('participant/groupe.twig', [
            'participant' => $participant,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Retire la participation de l'utilisateur à un jeu.
     */
    #[Route('/participant/{participant}/remove', name: 'participant.remove')]
    public function removeAction(EntityManagerInterface $entityManager, Request $request, #[MapEntity] Participant $participant)
    {
        $form = $this->createForm(ParticipantRemoveForm::class, $participant, ['gnId' => $participant->getGn()->getId()])
            ->add('save', SubmitType::class, ['label' => 'Oui, retirer la participation de cet utilisateur']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $participant = $form->getData();

            // si l'utilisateur est responsable d'un ou de plusieurs groupes, il faut mettre à jour ces groupes
            if ($participant->getGroupeGns()) {
                $groupeGns = $participant->getGroupeGns();
                foreach ($groupeGns as $groupeGn) {
                    $groupeGn->setResponsableNull();
                    $entityManager->persist($groupeGn);
                }
            }

            // on retire toutes les restauration liés à cet utilisateur.
            if ($participant->getParticipantHasRestaurations()) {
                $participantHasRestaurations = $participant->getParticipantHasRestaurations();
                foreach ($participantHasRestaurations as $restauration) {
                    $entityManager->remove($restauration);
                }
            }

            // on supprime aussi les réponses aux sondages
            if ($participant->getReponses()) {
                foreach ($participant->getReponses() as $reponse) {
                    $entityManager->remove($reponse);
                }
            }

            $entityManager->remove($participant);
            $entityManager->flush();

            $this->addFlash('success', 'Vos modifications ont été enregistrées.');

            return $this->redirectToRoute('gn.participants', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('participant/remove.twig', [
            'participant' => $participant,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajout d'un billet à un utilisateur. L'utilisateur doit participer au même jeu que celui du billet qui lui est affecté.
     */
    #[Route('/participant/{participant}/billet', name: 'participant.billet')]
    public function billetAction(EntityManagerInterface $entityManager, Request $request, #[MapEntity] Participant $participant)
    {
        $form = $this->createForm(ParticipantBilletForm::class, $participant)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $participant = $form->getData();
            $participant->setBilletDate(new \DateTime('NOW'));
            $entityManager->persist($participant);
            $entityManager->flush();

            // $app['notify']->newBillet($participant->getUser(), $participant->getBillet()); // TODO

            $this->addFlash('success', 'Vos modifications ont été enregistrées.');

            return $this->redirectToRoute('gn.participants', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('participant/billet.twig', [
            'participant' => $participant,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Choix du lieu de restauration d'un utilisateur.
     */
    #[Route('/participant/{participant}/restauration', name: 'participant.restauration')]
    public function restaurationAction(EntityManagerInterface $entityManager, Request $request, #[MapEntity] Participant $participant)
    {
        $originalParticipantHasRestaurations = new ArrayCollection();

        /*
         *  Crée un tableau contenant les objets ParticipantHasRestauration du participant
         */
        foreach ($participant->getParticipantHasRestaurations() as $participantHasRestauration) {
            $originalParticipantHasRestaurations->add($participantHasRestauration);
        }

        $form = $this->createForm(ParticipantRestaurationForm::class, $participant)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $participant = $form->getData();

            /*
             * Pour toutes les restaurations du participant
             */
            foreach ($participant->getParticipantHasRestaurations() as $participantHasRestauration) {
                $participantHasRestauration->setParticipant($participant);
            }

            /*
             *  supprime la relation entre participantHasRestauration et le participant
             */
            foreach ($originalParticipantHasRestaurations as $participantHasRestauration) {
                if (false == $participant->getParticipantHasRestaurations()->contains($participantHasRestauration)) {
                    $entityManager->remove($participantHasRestauration);
                }
            }

            $entityManager->persist($participant);
            $entityManager->flush();

            $this->addFlash('success', 'Vos modifications ont été enregistrées.');

            return $this->redirectToRoute('gn.participants', ['gn' => $participant->getGn()->getId()], 303);
        }

        dump($form->createView());

        return $this->render('participant/restauration.twig', [
            'participant' => $participant,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche le détail d'un personnage.
     */
    /*public function personnageAction(Request $request,  EntityManagerInterface $entityManager, Participant $participant)
    {
        $personnage = $participant->getPersonnage();

        if ( ! $personnage )
        {
           $this->addFlash('error','Vous n\'avez pas encore de personnage.');
            return $this->redirectToRoute('participant.index', array('participant' => $participant->getId())),303);
        }

        $lois = $entityManager->getRepository('App\Entity\Loi')->findAll();

        return $this->render('public/personnage/detail.twig', array(
                'personnage' => $personnage,
                'participant' => $participant,
                'lois' => $lois
        ));
    }*/
    /**
     * Fourni la page détaillant les relations entre les fiefs.
     */
    public function politiqueAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', "Vous n'avez pas encore de personnage.");

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->hasCompetence('Politique')) {
            $this->addFlash('error', 'Votre personnage ne dispose pas de la compétence Politique');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        // recherche de tous les groupes participant au prochain GN
        $gn = $app['larp.manager']->getGnActif();
        $groupeGns = $gn->getGroupeGns();
        $groupes = new ArrayCollection();
        foreach ($groupeGns as $groupeGn) {
            $groupe = $groupeGn->getGroupe();
            // if ( $groupe->getTerritoires()->count() > 0 )
            // {
            $groupes[] = $groupe;
            // }
        }

        return $this->render('public/personnage/politique.twig', [
            'personnage' => $personnage,
            'participant' => $participant,
            'groupes' => $groupes,
        ]);
    }

    /**
     * Modification de quelques informations concernant le personnage.
     */
    public function personnageEditAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', "Vous n'avez pas encore de personnage.");

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        $form = $this->createForm(PersonnageEditForm::class, $personnage)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();
            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Vos modifications ont été prises en compte.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('public/personnage/edit.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'participant' => $participant,
        ]);
    }

    /**
     * Modification de la photo lié à un personnage.
     */
    public function personnageTrombineAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, Personnage $personnage)
    {
        $form = $this->createForm(TrombineForm::class, [])
            ->add('envoyer', SubmitType::class, ['label' => 'Envoyer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $files = $request->files->get($form->getName());

            $path = __DIR__.'/../../../private/img/';
            $filename = $files['trombine']->getClientOriginalName();
            $extension = $files['trombine']->guessExtension();

            if (!$extension || !in_array($extension, ['png', 'jpg', 'jpeg', 'bmp'])) {
                $this->addFlash('error', 'Désolé, votre image ne semble pas valide (vérifiez le format de votre image)');

                return $this->redirectToRoute('participant.personnage.trombine', ['participant' => $participant->getId(), 'personnage' => $personnage->getId()], 303);
            }

            $trombineFilename = hash('md5', $this->getUser()->getUsername().$filename.time()).'.'.$extension;

            $image = $app['imagine']->open($files['trombine']->getPathname());
            $image->resize($image->getSize()->widen(160));
            $image->save($path.$trombineFilename);

            $personnage->setTrombineUrl($trombineFilename);
            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Votre photo a été enregistrée');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('public/personnage/trombine.twig', [
            'participant' => $participant,
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Retire un personnage à un participant.
     */
    #[Route('/participant/{participant}/remove/{personnage}', name: 'participant.personnage.remove')]
    public function personnageRemoveAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, Personnage $personnage)
    {
        $groupeGn = $participant->getGroupeGn();
        $groupe = $groupeGn->getGroupe();

        $form = $this->createForm()
            ->add('save', SubmitType::class, ['label' => 'Valider']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $participant->setPersonnageNull();
            $entityManager->persist($participant);
            $entityManager->flush();

            $this->addFlash('success', 'Votre modification a été enregistrée');

            return $this->redirectToRoute('groupe.detail', ['index' => $groupe->getId()]);
        }

        return $this->render('participant/personnage_remove.twig', [
            'form' => $form->createView(),
            'groupe' => $groupe,
            'participant' => $participant,
            'personnage' => $personnage,
        ]);
    }

    /**
     * Reprendre un ancien personnage.
     */
    public function personnageOldAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $groupeGn = $participant->getGroupeGn();

        if (!$groupeGn) {
            $this->addFlash('error', 'Désolé, vous devez rejoindre un groupe avant de pouvoir créer votre personnage.');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$participant->getBillet()) {
            $this->addFlash('error', 'Désolé, vous devez avoir un billet avant de pouvoir créer votre personnage.');

            return $this->redirectToRoute('gn.detail', ['gn' => $groupeGn->getGn()->getId()], 303);
        }

        if (true == $groupeGn->getGroupe()->getLock()) {
            $this->addFlash('error', 'Désolé, ce groupe est fermé. La création de personnage est temporairement désactivée.');

            return $this->redirectToRoute('gn.detail', ['gn' => $groupeGn->getGn()->getId()], 303);
        }

        if ($participant->getPersonnage()) {
            $this->addFlash('error', 'Désolé, vous disposez déjà d\'un personnage.');

            return $this->redirectToRoute('gn.detail', ['gn' => $groupeGn->getGn()->getId()], 303);
        }

        $groupe = $groupeGn->getGroupe();
        $gn = $groupeGn->getGn();

        $default = $participant->getUser()->getPersonnages()->toArray()[0];
        $lastPersonnage = $participant->getUser()->getLastPersonnage();
        if (null != $lastPersonnage) {
            $default = $lastPersonnage;
        }

        // error_log($default->getNom());

        $form = $this->createForm()
            ->add('personnage', 'entity', [
                'label' => 'Choisissez votre personnage',
                'choice_label' => 'resumeParticipations',
                'class' => Personnage::class,
                'choices' => array_unique($participant->getUser()->getPersonnagesVivants()),
                'data' => $default,
            ])
            ->add('save', SubmitType::class, ['label' => 'Valider']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $personnage = $data['personnage'];
            $participant->setPersonnage($personnage);

            $territoire = $groupe->getTerritoire();
            if ($territoire) {
                $langue = $territoire->getLangue();
                if ($langue && !$personnage->isKnownLanguage($langue)) {
                    $personnageLangue = new \App\Entity\PersonnageLangues();
                    $personnageLangue->setPersonnage($personnage);
                    $personnageLangue->setLangue($langue);
                    $personnageLangue->setSource('GROUPE');
                    $entityManager->persist($personnageLangue);
                }
            }

            // Chronologie : Participation au GN courant
            $anneeGN2 = $participant->getGn()->getDateJeu();
            $evenement2 = 'Participation '.$participant->getGn()->getLabel();
            $personnageChronologie2 = new \App\Entity\PersonnageChronologie();
            $personnageChronologie2->setAnnee($anneeGN2);
            $personnageChronologie2->setEvenement($evenement2);
            $personnageChronologie2->setPersonnage($personnage);

            // Activer les triggers automatique pour la Litérature et la Noblesse par exemple.
            foreach ($personnage->getCompetences() as $competence) {
                // Litterature initié : 1 sort 1 + 1 recette 1
                if ('Littérature' == $competence->getCompetenceFamily()->getLabel()) {
                    if (2 == $competence->getLevel()->getId()) {
                        $trigger = new \App\Entity\PersonnageTrigger();
                        $trigger->setPersonnage($personnage);
                        $trigger->setTag('SORT APPRENTI');
                        $trigger->setDone(false);
                        $entityManager->persist($trigger);

                        $trigger2 = new \App\Entity\PersonnageTrigger();
                        $trigger2->setPersonnage($personnage);
                        $trigger2->setTag('ALCHIMIE APPRENTI');
                        $trigger2->setDone(false);
                        $entityManager->persist($trigger2);
                    }

                    // Litterature expert : 1 sort 2 + 1 recette 2
                    if (3 == $competence->getLevel()->getId()) {
                        $trigger3 = new \App\Entity\PersonnageTrigger();
                        $trigger3->setPersonnage($personnage);
                        $trigger3->setTag('SORT INITIE');
                        $trigger3->setDone(false);
                        $entityManager->persist($trigger3);

                        $trigger4 = new \App\Entity\PersonnageTrigger();
                        $trigger4->setPersonnage($personnage);
                        $trigger4->setTag('ALCHIMIE INITIE');
                        $trigger4->setDone(false);
                        $entityManager->persist($trigger4);
                    }

                    // Litterature maitre : 1 sort 3 + 1 recette 3
                    if (4 == $competence->getLevel()->getId()) {
                        $trigger5 = new \App\Entity\PersonnageTrigger();
                        $trigger5->setPersonnage($personnage);
                        $trigger5->setTag('SORT EXPERT');
                        $trigger5->setDone(false);
                        $entityManager->persist($trigger5);

                        $trigger6 = new \App\Entity\PersonnageTrigger();
                        $trigger6->setPersonnage($personnage);
                        $trigger6->setTag('ALCHIMIE EXPERT');
                        $trigger6->setDone(false);
                        $entityManager->persist($trigger6);
                    }
                }

                // Noblesse expert : +2 Renommee
                if ('Noblesse' == $competence->getCompetenceFamily()->getLabel() && 3 == $competence->getLevel()->getId()) {
                    $renomme_history = new \App\Entity\RenommeHistory();
                    $renomme_history->setRenomme(2);
                    $renomme_history->setExplication('[Nouvelle participation] Noblesse Expert');
                    $renomme_history->setPersonnage($personnage);
                    $entityManager->persist($renomme_history);
                }
            }

            $entityManager->persist($personnageChronologie2);
            $entityManager->persist($participant);
            $entityManager->flush();

            $this->addFlash('success', 'Votre personnage a été sauvegardé.');

            return $this->redirectToRoute('gn.detail', ['gn' => $groupeGn->getGn()->getId()], 303);
        }

        return $this->render('public/participant/personnage_old.twig', [
            'form' => $form->createView(),
            'groupe' => $groupe,
            'participant' => $participant,
        ]);
    }

    /**
     * Reprendre un ancien personnage.
     */
    public function adminPersonnageOldAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $groupeGn = $participant->getGroupeGn();
        $groupe = $groupeGn->getGroupe();
        $gn = $groupeGn->getGn();

        $form = $this->createForm()
            ->add('personnage', 'entity', [
                'label' => 'Choisissez le personnage',
                'choice_label' => 'nom',
                'class' => Personnage::class,
                'choices' => array_unique($participant->getUser()->getPersonnages()->toArray()),
            ])
            ->add('save', SubmitType::class, ['label' => 'Valider']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $participant->setPersonnage($data['personnage']);

            $territoire = $groupe->getTerritoire();
            if ($territoire) {
                $langue = $territoire->getLangue();
                if ($langue && !$data['personnage']->isKnownLanguage($langue)) {
                    $personnageLangue = new \App\Entity\PersonnageLangues();
                    $personnageLangue->setPersonnage($data['personnage']);
                    $personnageLangue->setLangue($langue);
                    $personnageLangue->setSource('GROUPE');
                    $entityManager->persist($personnageLangue);
                }
            }

            // Chronologie : Participation au GN courant
            $anneeGN2 = $participant->getGn()->getDateJeu();
            $evenement2 = 'Participation '.$participant->getGn()->getLabel();
            $personnageChronologie2 = new \App\Entity\PersonnageChronologie();
            $personnageChronologie2->setAnnee($anneeGN2);
            $personnageChronologie2->setEvenement($evenement2);
            $personnageChronologie2->setPersonnage($data['personnage']);

            $entityManager->persist($personnageChronologie2);
            $entityManager->persist($participant);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('gn.participants.withoutperso', ['gn' => $gn->getId()], 303);
        }

        return $this->render('participant/personnage_old.twig', [
            'form' => $form->createView(),
            'groupe' => $groupe,
            'participant' => $participant,
        ]);
    }

    /**
     * Création d'un nouveau personnage. L'utilisateur doit être dans un groupe et son billet doit être valide.
     */
    public function personnageNewAction(Request $request, EntityManagerInterface $entityManager, Participant $participant): RedirectResponse|Response
    {
        $groupeGn = $participant->getGroupeGn();

        if (!$groupeGn) {
            $this->addFlash('error', 'Désolé, vous devez rejoindre un groupe avant de pouvoir créer votre personnage.');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$participant->getBillet()) {
            $this->addFlash('error', 'Désolé, vous devez avoir un billet avant de pouvoir créer votre personnage.');

            return $this->redirectToRoute('gn.detail', ['gn' => $groupeGn->getGn()->getId()], 303);
        }

        if (true === $groupeGn->getGroupe()->getLock()) {
            $this->addFlash('error', 'Désolé, ce groupe est fermé. La création de personnage est temporairement désactivée.');

            return $this->redirectToRoute('gn.detail', ['gn' => $groupeGn->getGn()->getId()], 303);
        }

        if ($participant->getPersonnage()) {
            $this->addFlash('error', 'Désolé, vous disposez déjà d\'un personnage.');

            return $this->redirectToRoute('gn.detail', ['gn' => $groupeGn->getGn()->getId()], 303);
        }

        $groupe = $groupeGn->getGroupe();

        /*if (  ! $groupe->hasEnoughClasse($groupeGn->getGn()) )
        {
           $this->addFlash('error','Désolé, ce groupe ne contient plus de classes disponibles');
            return $this->redirectToRoute('participant.index', array('participant' => $participant->getId())),303);
        }*/

        $personnage = new Personnage();
        $classes = $entityManager->getRepository('\\'.\App\Entity\Classe::class)->findAllCreation();

        // j'ajoute ici certains champs du formulaires (les classes)
        // car j'ai besoin des informations du groupe pour les alimenter
        $form = $this->createForm(PersonnageForm::class, $personnage)
            ->add('classe', 'entity', [
                'label' => 'Classes disponibles',
                'choice_label' => 'label',
                'class' => \App\Entity\Classe::class,
                'choices' => array_unique($classes),
            ])
            ->add('save', SubmitType::class, ['label' => 'Valider mon personnage', 'attr' => ['onclick' => "return confirm('Confirmez vous le personnage ?')"]]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();

            $personnage->setUser($this->getUser());
            $participant->setPersonnage($personnage);

            // Ajout des points d'expérience gagné à la création d'un personnage
            $personnage->setXp($participant->getGn()->getXpCreation());

            // Set basic age
            $age = $personnage->getAge()->getMinimumValue();
            $age += random_int(0, 4);
            $personnage->setAgeReel($age);

            // Chronologie : Naissance
            $anneeGN = $participant->getGn()->getDateJeu() - $age;
            $evenement = 'Naissance';
            $personnageChronologie = new \App\Entity\PersonnageChronologie();
            $personnageChronologie->setAnnee($anneeGN);
            $personnageChronologie->setEvenement($evenement);
            $personnageChronologie->setPersonnage($personnage);
            $entityManager->persist($personnageChronologie);

            // Chronologie : Participation au GN courant
            $anneeGN2 = $participant->getGn()->getDateJeu();
            $evenement2 = 'Participation '.$participant->getGn()->getLabel();
            $personnageChronologie2 = new \App\Entity\PersonnageChronologie();
            $personnageChronologie2->setAnnee($anneeGN2);
            $personnageChronologie2->setEvenement($evenement2);
            $personnageChronologie2->setPersonnage($personnage);
            $entityManager->persist($personnageChronologie2);

            // historique
            $historique = new \App\Entity\ExperienceGain();
            $historique->setExplanation('Création de votre personnage');
            $historique->setOperationDate(new \DateTime('NOW'));
            $historique->setPersonnage($personnage);
            $historique->setXpGain($participant->getGn()->getXpCreation());
            $entityManager->persist($historique);

            // ajout des compétences acquises à la création
            foreach ($personnage->getClasse()->getCompetenceFamilyCreations() as $competenceFamily) {
                $firstCompetence = $competenceFamily->getFirstCompetence();
                if ($firstCompetence) {
                    $personnage->addCompetence($firstCompetence);
                    $firstCompetence->addPersonnage($personnage);
                    $entityManager->persist($firstCompetence);
                }

                if ('Noblesse' === $competenceFamily->getLabel()) {
                    $personnage->addRenomme(2);
                    $renomme_history = new \App\Entity\RenommeHistory();

                    $renomme_history->setRenomme(2);
                    $renomme_history->setExplication('Compétence Noblesse niveau 1');
                    $renomme_history->setPersonnage($personnage);
                    $entityManager->persist($renomme_history);
                }
            }

            // Ajout des points d'expérience gagné grace à l'age du personnage ou perdu à cause de l'age du joueur
            $age_joueur = $participant->getAgeJoueur();
            if ($age_joueur >= 16) {
                $xpAgeBonus = $personnage->getAge()->getBonus();
            } elseif ($age_joueur >= 12) {
                $xpAgeBonus = -3;
            } elseif ($age_joueur >= 9) {
                $xpAgeBonus = -6;
            } else {
                $xpAgeBonus = -10;
            }

            if ($xpAgeBonus) {
                $personnage->addXp($xpAgeBonus);
                $historique = new \App\Entity\ExperienceGain();
                $historique->setExplanation("Modification liée à l'age");
                $historique->setOperationDate(new \DateTime('NOW'));
                $historique->setPersonnage($personnage);
                $historique->setXpGain($xpAgeBonus);
                $entityManager->persist($historique);
            }

            // Ajout des langues en fonction de l'origine du personnage
            $langue = $personnage->getOrigine()->getLangue();
            if ($langue) {
                $personnageLangue = new \App\Entity\PersonnageLangues();
                $personnageLangue->setPersonnage($personnage);
                $personnageLangue->setLangue($langue);
                $personnageLangue->setSource('ORIGINE');
                $entityManager->persist($personnageLangue);
            }

            // Ajout des langues secondaires lié à l'origine du personnage
            foreach ($personnage->getOrigine()->getLangues() as $langue) {
                if (!$personnage->isKnownLanguage($langue)) {
                    $personnageLangue = new \App\Entity\PersonnageLangues();
                    $personnageLangue->setPersonnage($personnage);
                    $personnageLangue->setLangue($langue);
                    $personnageLangue->setSource('ORIGINE SECONDAIRE');
                    $entityManager->persist($personnageLangue);
                }
            }

            // Ajout de la langue du groupe
            $territoire = $groupe->getTerritoire();
            if ($territoire) {
                $langue = $territoire->getLangue();
                if ($langue && !$personnage->isKnownLanguage($langue)) {
                    $personnageLangue = new \App\Entity\PersonnageLangues();
                    $personnageLangue->setPersonnage($personnage);
                    $personnageLangue->setLangue($langue);
                    $personnageLangue->setSource('GROUPE');
                    $entityManager->persist($personnageLangue);
                }
            }

            $entityManager->persist($personnage);
            $entityManager->persist($participant);
            $entityManager->flush();

            $this->addFlash('success', 'Votre personnage a été sauvegardé.');

            return $this->redirectToRoute('gn.detail', ['gn' => $groupeGn->getGn()->getId()], 303);
        }

        $ages = $entityManager->getRepository(\App\Entity\Age::class)->findAllOnCreation();
        $territoires = $entityManager->getRepository(\App\Entity\Territoire::class)->findRoot();

        return $this->render('public/participant/personnage_new.twig', [
            'form' => $form->createView(),
            'classes' => array_unique($classes),
            'groupe' => $groupe,
            'participant' => $participant,
            'ages' => $ages,
            'territoires' => $territoires,
        ]);
    }

    /**
     * Création d'un nouveau personnage. L'utilisateur doit être dans un groupe et son billet doit être valide.
     */
    public function adminPersonnageNewAction(Request $request, EntityManagerInterface $entityManager)
    {
        $participant = $request->get('participant');
        $participant = $entityManager->getRepository('\\'.Participant::class)->find($participant);

        $groupeGn = $participant->getGroupeGn();
        $groupe = $groupeGn->getGroupe();

        $personnage = new Personnage();
        $classes = $entityManager->getRepository('\\'.\App\Entity\Classe::class)->findAllCreation();

        // j'ajoute içi certain champs du formulaires (les classes)
        // car j'ai besoin des informations du groupe pour les alimenter
        $form = $this->createForm(PersonnageForm::class, $personnage)
            ->add('classe', 'entity', [
                'label' => 'Classes disponibles',
                'choice_label' => 'label',
                'class' => \App\Entity\Classe::class,
                'choices' => array_unique($classes),
            ])
            ->add('save', SubmitType::class, ['label' => 'Valider le personnage']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();
            $personnage->setUser($participant->getUser());
            $personnage->setGroupe($participant->getGroupeGn()->getGroupe());
            $participant->setPersonnage($personnage);

            // Ajout des points d'expérience gagné à la création d'un personnage
            $personnage->setXp($participant->getGn()->getXpCreation());

            // historique
            $historique = new \App\Entity\ExperienceGain();
            $historique->setExplanation('Création de votre personnage');
            $historique->setOperationDate(new \DateTime('NOW'));
            $historique->setPersonnage($personnage);
            $historique->setXpGain($participant->getGn()->getXpCreation());
            $entityManager->persist($historique);

            // ajout des compétences acquises à la création
            foreach ($personnage->getClasse()->getCompetenceFamilyCreations() as $competenceFamily) {
                $firstCompetence = $competenceFamily->getFirstCompetence();
                if ($firstCompetence) {
                    $personnage->addCompetence($firstCompetence);
                    $firstCompetence->addPersonnage($personnage);
                    $entityManager->persist($firstCompetence);
                }

                if ('Noblesse' == $competenceFamily->getLabel()) {
                    $personnage->addRenomme(2);
                    $renomme_history = new \App\Entity\RenommeHistory();

                    $renomme_history->setRenomme(2);
                    $renomme_history->setExplication('Compétence Noblesse niveau 1');
                    $renomme_history->setPersonnage($personnage);
                    $entityManager->persist($renomme_history);
                }
            }

            // Ajout des points d'expérience gagné grace à l'age
            $xpAgeBonus = $personnage->getAge()->getBonus();
            if ($xpAgeBonus) {
                $personnage->addXp($xpAgeBonus);
                $historique = new \App\Entity\ExperienceGain();
                $historique->setExplanation("Bonus lié à l'age");
                $historique->setOperationDate(new \DateTime('NOW'));
                $historique->setPersonnage($personnage);
                $historique->setXpGain($xpAgeBonus);
                $entityManager->persist($historique);
            }

            // Ajout des langues en fonction de l'origine du personnage
            $langue = $personnage->getOrigine()->getLangue();
            if ($langue) {
                $personnageLangue = new \App\Entity\PersonnageLangues();
                $personnageLangue->setPersonnage($personnage);
                $personnageLangue->setLangue($langue);
                $personnageLangue->setSource('ORIGINE');
                $entityManager->persist($personnageLangue);
            }

            // Ajout des langues secondaires lié à l'origine du personnage
            foreach ($personnage->getOrigine()->getLangues() as $langue) {
                if (!$personnage->isKnownLanguage($langue)) {
                    $personnageLangue = new \App\Entity\PersonnageLangues();
                    $personnageLangue->setPersonnage($personnage);
                    $personnageLangue->setLangue($langue);
                    $personnageLangue->setSource('ORIGINE SECONDAIRE');
                    $entityManager->persist($personnageLangue);
                }
            }

            // Ajout de la langue du groupe
            $territoire = $groupe->getTerritoire();
            if ($territoire) {
                $langue = $territoire->getLangue();
                if ($langue && !$personnage->isKnownLanguage($langue)) {
                    $personnageLangue = new \App\Entity\PersonnageLangues();
                    $personnageLangue->setPersonnage($personnage);
                    $personnageLangue->setLangue($langue);
                    $personnageLangue->setSource('GROUPE');
                    $entityManager->persist($personnageLangue);
                }
            }

            $entityManager->persist($personnage);
            $entityManager->persist($participant);
            $entityManager->flush();

            $this->addFlash('success', 'Votre personnage a été sauvegardé.');

            return $this->redirectToRoute('gn.participants.withoutperso', ['gn' => $groupeGn->getGn()->getId()], 303);
        }

        $ages = $entityManager->getRepository(\App\Entity\Age::class)->findAllOnCreation();
        $territoires = $entityManager->getRepository(\App\Entity\Territoire::class)->findRoot();

        return $this->render('participant/personnage_new.twig', [
            'form' => $form->createView(),
            'classes' => array_unique($classes),
            'groupe' => $groupe,
            'participant' => $participant,
            'ages' => $ages,
            'territoires' => $territoires,
        ]);
    }

    /**
     * Page listant les règles à télécharger.
     */
    public function regleListAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $regles = $entityManager->getRepository(Rule::class)->findAll();

        return $this->render('public/rule/list.twig', [
            'regles' => $regles,
            'participant' => $participant,
        ]);
    }

    /**
     * Détail d'une règle.
     */
    #[Route('/{participant}/regle/{rule}', name: 'participant.regle.detail')]
    public function regleDetailAction(Request $request, Participant $participant, Rule $rule)
    {
        return $this->render('public/rule/detail.twig', [
            'regle' => $rule,
            'participant' => $participant,
        ]);
    }

    /**
     * Télécharger une règle.
     *
     * @param Rule rule
     */
    public function regleDocumentAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, Rule $rule)
    {
        $filename = __DIR__.'/../../../private/rules/'.$rule->getUrl();

        return $app->sendFile($filename);
    }

    /**
     * Rejoindre un groupe.
     */

    #[Route('/participant/groupe/join', name: 'participant.groupe.join')]
    public function groupeJoinAction(Request $request, EntityManagerInterface $entityManager, Participant $participant): RedirectResponse|Response
    {
        // il faut un billet pour rejoindre un groupe
        if (!$participant->getBillet()) {
            $this->addFlash('error', 'Désolé, vous devez obtenir un billet avant de pouvoir rejoindre un groupe');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        $form = $this->createForm(GroupeInscriptionForm::class, [])
            ->add('subscribe', SubmitType::class, ['label' => "S'inscrire"]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $code = $data['code'];
            $groupeGn = $entityManager->getRepository('\\'.\App\Entity\GroupeGn::class)->findOneByCode($code);
            if (!$groupeGn) {
                $this->addFlash('error', 'Désolé, le code que vous utilisez ne correspond à aucun groupe');

                return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
            }

            $groupe = $groupeGn->getGroupe();
            if (!$groupe) {
                // Il est possible que ce cas ne puisse pas arriver.
                $this->addFlash('error', 'Désolé, le code que vous utilisez correspond à un groupe mal paramétré');

                return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
            }

            $groupeGn = $groupe->getGroupeGn($participant->getGn());
            if (!$groupeGn) {
                $this->addFlash('error', 'Le code correspond à un groupe qui ne participe pas à cette session de jeu.');

                return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
            }

            // il faut que le groupe ai un responsable pour le rejoindre
            if (!$groupeGn->getResponsable()) {
                $this->addFlash('error', "Le groupe n'a pas encore de responsable, vous ne pouvez pas le rejoindre pour le moment.");

                return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
            }

            $participant->setGroupeGn($groupeGn);
            $entityManager->persist($participant);
            $entityManager->flush();

            // envoyer une notification au chef de groupe et au scénariste
            $app['notify']->joinGroupe($participant, $groupeGn);

            $this->addFlash('success', 'Vous avez rejoint le groupe.');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('public/groupe/join.twig', [
            'form' => $form->createView(),
            'participant' => $participant,
        ]);
    }

    /**
     * Choix du personnage secondaire par un utilisateur.
     */
    public function personnageSecondaireAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\PersonnageSecondaire::class);
        $personnageSecondaires = $repo->findAll();

        $form = $this->createForm(ParticipantPersonnageSecondaireForm::class, $participant)
            ->add('choice', SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $participant = $form->getData();
            $entityManager->persist($participant);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage secondaire a été enregistré.');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGroupeGn()->getGn()->getId()], 303);
        }

        return $this->render('public/participant/personnageSecondaire.twig', [
            'participant' => $participant,
            'personnageSecondaires' => $personnageSecondaires,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Liste des background pour le joueur.
     */
    public function backgroundAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        // l'utilisateur doit avoir un personnage
        $personnage = $participant->getPersonnage();
        if (!$personnage) {
            $this->addFlash('error', 'Désolé, Vous devez faire votre personnage pour pouvoir consulter votre background.');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        $backsGroupe = new ArrayCollection();
        $backsJoueur = new ArrayCollection();

        // recherche les backgrounds liés au personnage (visibilité == OWNER)
        $backsJoueur = $personnage->getBackgrounds('OWNER');

        // recherche les backgrounds liés au groupe (visibilité == PUBLIC)
        $backsGroupe = new ArrayCollection(array_merge(
            $participant->getGroupeGn()->getGroupe()->getBacks('PUBLIC')->toArray(),
            $backsGroupe->toArray()
        ));

        // recherche les backgrounds liés au groupe (visibilité == GROUP_MEMBER)
        $backsGroupe = new ArrayCollection(array_merge(
            $participant->getGroupeGn()->getGroupe()->getBacks('GROUPE_MEMBER')->toArray(),
            $backsGroupe->toArray()
        ));

        // recherche les backgrounds liés au groupe (visibilité == GROUP_OWNER)
        if ($this->getUser() == $participant->getGroupeGn()->getGroupe()->getUserRelatedByResponsableId()) {
            $backsGroupe = new ArrayCollection(array_merge(
                $participant->getGroupeGn()->getGroupe()->getBacks('GROUPE_OWNER')->toArray(),
                $backsGroupe->toArray()
            ));
        }

        return $this->render('public/participant/background.twig', [
            'backsJoueur' => $backsJoueur,
            'backsGroupe' => $backsGroupe,
            'personnage' => $personnage,
            'participant' => $participant,
        ]);
    }

    /**
     * Mise à jour de l'origine d'un personnage.
     * Impossible si le personnage dispose déjà d'une origine.
     */
    public function origineAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Désolé, vous devez créer un personnage avant de choisir son origine.');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (true == $personnage->getGroupe()->getLock()) {
            $this->addFlash('error', 'Désolé, il n\'est plus possible de modifier ce personnage. Le groupe est verouillé. Contacter votre scénariste si vous pensez que cela est une erreur');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        if ($personnage->getTerritoire()) {
            $this->addFlash('error', 'Désolé, il n\'est pas possible de modifier votre origine. Veuillez contacter votre orga pour exposer votre problème.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        $form = $this->createForm(PersonnageOriginForm::class, $personnage)
            ->add('save', SubmitType::class, ['label' => 'Valider votre origine']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();
            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Votre personnage a été sauvegardé.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('public/participant/origine.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'participant' => $participant,
        ]);
    }

    /**
     * Liste des religions.
     */
    public function religionListAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\Religion::class);
        $religions = $repo->findAllOrderedByLabel();

        return $this->render('public/participant/religion.twig', [
            'religions' => $religions,
            'participant' => $participant,
        ]);
    }

    /**
     * Ajoute une religion au personnage.
     */
    public function religionAddAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage avant de choisir une religion !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (true == $participant->getGroupeGn()->getGroupe()->getLock()) {
            $this->addFlash('error', 'Désolé, il n\'est plus possible de modifier ce personnage. Le groupe est verouillé. Contactez votre scénariste si vous pensez que cela est une erreur');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        // refUser la demande si le personnage est Fanatique
        if ($personnage->isFanatique()) {
            $this->addFlash('error', 'Désolé, vous êtes un Fanatique, il vous est impossible de choisir une nouvelle religion. Veuillez contacter votre orga en cas de problème.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        $personnageReligion = new \App\Entity\PersonnagesReligions();
        $personnageReligion->setPersonnage($personnage);

        // ne proposer que les religions que le personnage ne pratique pas déjà ...
        $availableReligions = $app['personnage.manager']->getAvailableReligions($personnage);

        if (0 == $availableReligions->count()) {
            $this->addFlash('error', 'Désolé, il n\'y a plus de religion disponibles ( Sérieusement ? vous êtes éclectique, c\'est bien, mais ... faudrait savoir ce que vous voulez non ? L\'heure n\'est-il pas venu de faire un choix parmi tous ces dieux ?)');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        // construit le tableau de choix
        $choices = [];
        foreach ($availableReligions as $religion) {
            $choices[] = $religion;
        }

        $form = $this->createForm(PersonnageReligionForm::class, $personnageReligion)
            ->add('religion', 'entity', [
                'required' => true,
                'label' => 'Votre religion',
                'class' => \App\Entity\Religion::class,
                'choices' => $availableReligions,
                'choice_label' => 'label',
            ])
            ->add('save', SubmitType::class, ['label' => 'Valider votre religion']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnageReligion = $form->getData();

            // supprimer toutes les autres religions si l'utilisateur à choisi fanatique
            // n'autoriser que un Fervent que si l'utilisateur n'a pas encore Fervent.
            if (3 == $personnageReligion->getReligionLevel()->getIndex()) {
                $personnagesReligions = $personnage->getPersonnagesReligions();
                foreach ($personnagesReligions as $oldReligion) {
                    $entityManager->remove($oldReligion);
                }
            } elseif (2 == $personnageReligion->getReligionLevel()->getIndex()) {
                if ($personnage->isFervent()) {
                    $this->addFlash('error', 'Désolé, vous êtes déjà Fervent d\'une autre religion, il vous est impossible de choisir une nouvelle religion en tant que Fervent. Veuillez contacter votre orga en cas de problème.');

                    return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
                }
            }

            $entityManager->persist($personnageReligion);
            $entityManager->flush();

            $this->addFlash('success', 'Votre personnage a été sauvegardé.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('public/personnage/religion_add.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'participant' => $participant,
            'religions' => $availableReligions,
        ]);
    }

    /**
     * Detail d'une priere.
     */
    public function priereDetailAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, Priere $priere)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->isKnownPriere($priere)) {
            $this->addFlash('error', 'Vous ne connaissez pas cette prière !');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('public/priere/detail.twig', [
            'priere' => $priere,
            'participant' => $participant,
            'filename' => $priere->getPrintLabel(),
        ]);
    }

    /**
     * Obtenir le document lié à une priere.
     */
    public function priereDocumentAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, Priere $priere)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->isKnownPriere($priere)) {
            $this->addFlash('error', 'Vous ne connaissez pas cette prière !');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        $file = __DIR__.'/../../../private/doc/'.$priere->getDocumentUrl();

        return $app->sendFile($file)
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $priere->getPrintLabel().'.pdf');
    }

    /**
     * Obtenir le document lié à une technologie.
     */
    public function technologieDocumentAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, Technologie $technologie)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->isKnownTechnologie($technologie)) {
            $this->addFlash('error', 'Vous ne connaissez pas cette technologie !');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        $file = __DIR__.'/../../../private/doc/'.$technologie->getDocumentUrl();

        return $app->sendFile($file)
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $technologie->getPrintLabel().'.pdf');
    }

    /**
     * Detail d'une potion.
     */
    public function potionDetailAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, Potion $potion)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->isKnownPotion($potion)) {
            $this->addFlash('error', 'Vous ne connaissez pas cette potion !');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('public/potion/detail.twig', [
            'potion' => $potion,
            'participant' => $participant,
            'filename' => $potion->getPrintLabel(),
        ]);
    }

    /**
     * Obtenir le document lié à une potion.
     */
    public function potionDocumentAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, Potion $potion)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->isKnownPotion($potion)) {
            $this->addFlash('error', 'Vous ne connaissez pas cette potion !');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        $file = __DIR__.'/../../../private/doc/'.$potion->getDocumentUrl();

        return $app->sendFile($file)
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $potion->getPrintLabel().'.pdf');
    }

    /**
     * Choix d'une nouvelle potion.
     */
    public function potionAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        $niveau = $request->get('niveau');

        if (!$personnage->hasTrigger('ALCHIMIE APPRENTI')
            && !$personnage->hasTrigger('ALCHIMIE INITIE')
            && !$personnage->hasTrigger('ALCHIMIE EXPERT')
            && !$personnage->hasTrigger('ALCHIMIE MAITRE')) {
            $this->addFlash('error', 'Désolé, vous ne pouvez pas choisir de potions supplémentaires.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        $repo = $entityManager->getRepository('\\'.Potion::class);
        $potions = $repo->findByNiveau($niveau);

        $form = $this->createForm()
            ->add('potions', 'entity', [
                'required' => true,
                'label' => 'Choisissez votre potion',
                'multiple' => false,
                'expanded' => true,
                'class' => Potion::class,
                'choices' => $potions,
                'choice_label' => 'fullLabel',
            ])
            ->add('save', SubmitType::class, ['label' => 'Valider votre potion']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $potion = $data['potions'];

            // Ajout de la potion au personnage
            $personnage->addPotion($potion);
            $entityManager->persist($personnage);

            // suppression du trigger
            switch ($niveau) {
                case 1:
                    $trigger = $personnage->getTrigger('ALCHIMIE APPRENTI');
                    $entityManager->remove($trigger);
                    break;
                case 2:
                    $trigger = $personnage->getTrigger('ALCHIMIE INITIE');
                    $entityManager->remove($trigger);
                    break;
                case 3:
                    $trigger = $personnage->getTrigger('ALCHIMIE EXPERT');
                    $entityManager->remove($trigger);
                    break;
                case 4:
                    $trigger = $personnage->getTrigger('ALCHIMIE MAITRE');
                    $entityManager->remove($trigger);
                    break;
            }

            $entityManager->flush();

            $this->addFlash('success', 'Vos modifications ont été enregistrées.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('personnage/potion.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'participant' => $participant,
            'potions' => $potions,
            'niveau' => $niveau,
        ]);
    }

    /**
     * Choix d'une nouvelle potion de départ.
     */
    public function potiondepartAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        $niveau = $request->get('niveau');

        if ($participant->hasPotionsDepartByLevel($niveau)) {
            $this->addFlash('error', 'Désolé, vous ne pouvez pas choisir de potions de départ supplémentaires.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        $potions = $personnage->getPotionsNiveau($niveau);

        $form = $this->createForm()
            ->add('potions', 'entity', [
                'required' => true,
                'label' => 'Choisissez votre potion de départ',
                'multiple' => false,
                'expanded' => true,
                'class' => Potion::class,
                'choices' => $potions,
                'choice_label' => 'fullLabel',
            ])
            ->add('save', SubmitType::class, ['label' => 'Valider votre potion de départ']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $potion = $data['potions'];

            // Ajout de la potion au personnage
            $participant->addPotionDepart($potion);
            $entityManager->persist($participant);

            $entityManager->flush();

            $this->addFlash('success', 'Vos modifications ont été enregistrées.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('personnage/potiondepart.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'participant' => $participant,
            'potions' => $potions,
            'niveau' => $niveau,
        ]);
    }

    /**
     * Choix d'une nouvelle description de religion.
     */
    public function religionDescriptionAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->hasTrigger('PRETRISE INITIE')) {
            $this->addFlash('error', 'Désolé, vous ne pouvez pas choisir de descriptif de religion supplémentaire.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        $availableDescriptionReligion = $app['personnage.manager']->getAvailableDescriptionReligion($personnage);

        $form = $this->createForm()
            ->add('religion', 'entity', [
                'required' => true,
                'label' => 'Choisissez votre nouveau descriptif religion',
                'multiple' => false,
                'expanded' => true,
                'class' => \App\Entity\Religion::class,
                'choices' => $availableDescriptionReligion,
                'choice_label' => 'label',
            ])
            ->add('save', SubmitType::class, ['label' => 'Valider']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $religion = $data['religion'];

            $personnage->addReligion($religion);

            $trigger = $personnage->getTrigger('PRETRISE INITIE');
            $entityManager->remove($trigger);

            $entityManager->flush();

            $this->addFlash('success', 'Vos modifications ont été enregistrées.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('public/personnage/descriptionReligion.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'participant' => $participant,
        ]);
    }

    /**
     * Choix d'une nouvelle langue commune.
     */
    public function langueCommuneAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->hasTrigger('LANGUE COMMUNE')) {
            $this->addFlash('error', 'Désolé, vous ne pouvez pas choisir de langue commune supplémentaire.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        $availableLangues = $app['personnage.manager']->getAvailableLangues($personnage, 2);

        $form = $this->createForm()
            ->add('langue', 'entity', [
                'required' => true,
                'label' => 'Choisissez votre nouvelle langue',
                'multiple' => false,
                'expanded' => true,
                'class' => Langue::class,
                'choices' => $availableLangues,
                'choice_label' => 'fullDescription',
            ])
            ->add('save', SubmitType::class, ['label' => 'Valider votre nouvelle langue']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $langue = $data['langue'];

            $personnageLangue = new \App\Entity\PersonnageLangues();
            $personnageLangue->setPersonnage($personnage);
            $personnageLangue->setLangue($langue);
            $personnageLangue->setSource('LITTERATURE');
            $entityManager->persist($personnageLangue);

            $trigger = $personnage->getTrigger('LANGUE COMMUNE');
            $entityManager->remove($trigger);

            $entityManager->flush();

            $this->addFlash('success', 'Vos modifications ont été enregistrées.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('public/personnage/langueCommune.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'participant' => $participant,
        ]);
    }

    /**
     * Choix d'une nouvelle langue courante.
     */
    public function langueCouranteAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->hasTrigger('LANGUE COURANTE')) {
            $this->addFlash('error', 'Désolé, vous ne pouvez pas choisir de langue courante supplémentaire.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        $availableLangues = $app['personnage.manager']->getAvailableLangues($personnage, 1);

        $form = $this->createForm()
            ->add('langue', 'entity', [
                'required' => true,
                'label' => 'Choisissez votre nouvelle langue',
                'multiple' => false,
                'expanded' => true,
                'class' => Langue::class,
                'choices' => $availableLangues,
                'choice_label' => 'fullDescription',
            ])
            ->add('save', SubmitType::class, ['label' => 'Valider votre nouvelle langue']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $langue = $data['langue'];

            $personnageLangue = new \App\Entity\PersonnageLangues();
            $personnageLangue->setPersonnage($personnage);
            $personnageLangue->setLangue($langue);
            $personnageLangue->setSource('LITTERATURE');
            $entityManager->persist($personnageLangue);

            $trigger = $personnage->getTrigger('LANGUE COURANTE');
            $entityManager->remove($trigger);

            $entityManager->flush();

            $this->addFlash('success', 'Vos modifications ont été enregistrées.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('public/personnage/langueCourante.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'participant' => $participant,
        ]);
    }

    /**
     * Choix d'une nouvelle langue ancienne.
     */
    public function langueAncienneAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->hasTrigger('LANGUE ANCIENNE')) {
            $this->addFlash('error', 'Désolé, vous ne pouvez pas choisir de langue ancienne supplémentaire.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        $availableLangues = $app['personnage.manager']->getAvailableLangues($personnage, 0);

        $form = $this->createForm()
            ->add('langue', 'entity', [
                'required' => true,
                'label' => 'Choisissez votre nouvelle langue',
                'multiple' => false,
                'expanded' => true,
                'class' => Langue::class,
                'choices' => $availableLangues,
                'choice_label' => 'fullDescription',
            ])
            ->add('save', SubmitType::class, ['label' => 'Valider votre nouvelle langue']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $langue = $data['langue'];

            $personnageLangue = new \App\Entity\PersonnageLangues();
            $personnageLangue->setPersonnage($personnage);
            $personnageLangue->setLangue($langue);
            $personnageLangue->setSource('LITTERATURE');
            $entityManager->persist($personnageLangue);

            $trigger = $personnage->getTrigger('LANGUE ANCIENNE');
            $entityManager->remove($trigger);

            $entityManager->flush();

            $this->addFlash('success', 'Vos modifications ont été enregistrées.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('public/personnage/langueAncienne.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'participant' => $participant,
        ]);
    }

    /**
     * Obtenir le document lié à une langue.
     */
    public function langueDocumentAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, Langue $langue)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->isKnownLanguage($langue)) {
            $this->addFlash('error', 'Vous ne connaissez pas cette langue !');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        $file = __DIR__.'/../../../private/doc/'.$langue->getDocumentUrl();

        return $app->sendFile($file)
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $langue->getPrintLabel().'.pdf');
    }

    /**
     * Choix d'un domaine de magie.
     */
    public function domaineMagieAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->hasTrigger('DOMAINE MAGIE')) {
            $this->addFlash('error', 'Désolé, vous ne pouvez pas choisir de domaine de magie supplémentaire.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        $availableDomaines = $app['personnage.manager']->getAvailableDomaines($personnage);

        $form = $this->createForm()
            ->add('domaine', 'entity', [
                'required' => true,
                'label' => 'Choisissez votre domaine de magie',
                'multiple' => false,
                'expanded' => true,
                'class' => \App\Entity\Domaine::class,
                'choices' => $availableDomaines,
                'choice_label' => 'label',
            ])
            ->add('save', SubmitType::class, ['label' => 'Valider votre domaine de magie']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $domaine = $data['domaine'];

            // Ajout du domaine de magie au personnage
            $personnage->addDomaine($domaine);
            $entityManager->persist($personnage);

            // suppression du trigger
            $trigger = $personnage->getTrigger('DOMAINE MAGIE');
            $entityManager->remove($trigger);

            $entityManager->flush();

            $this->addFlash('success', 'Vos modifications ont été enregistrées.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('personnage/domaineMagie.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'participant' => $participant,
            'domaines' => $availableDomaines,
        ]);
    }

    /**
     * Choix d'un nouveau sortilège.
     */
    public function sortAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        $niveau = $request->get('niveau');

        if (!$personnage->hasTrigger('SORT APPRENTI')
            && !$personnage->hasTrigger('SORT INITIE')
            && !$personnage->hasTrigger('SORT EXPERT')
            && !$personnage->hasTrigger('SORT MAITRE')) {
            $this->addFlash('error', 'Désolé, vous ne pouvez pas choisir de sorts supplémentaires.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        $sorts = $app['personnage.manager']->getAvailableSorts($personnage, $niveau);

        $form = $this->createForm()
            ->add('sorts', 'entity', [
                'required' => true,
                'label' => 'Choisissez votre sort',
                'multiple' => false,
                'expanded' => true,
                'class' => Sort::class,
                'choices' => $sorts,
                'choice_label' => 'fullLabel',
            ])
            ->add('save', SubmitType::class, ['label' => 'Valider votre sort']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $sort = $data['sorts'];

            // Ajout du domaine de magie au personnage
            $personnage->addSort($sort);
            $entityManager->persist($personnage);

            // suppression du trigger
            switch ($niveau) {
                case 1:
                    $trigger = $personnage->getTrigger('SORT APPRENTI');
                    $entityManager->remove($trigger);
                    break;
                case 2:
                    $trigger = $personnage->getTrigger('SORT INITIE');
                    $entityManager->remove($trigger);
                    break;
                case 3:
                    $trigger = $personnage->getTrigger('SORT EXPERT');
                    $entityManager->remove($trigger);
                    break;
                case 4:
                    $trigger = $personnage->getTrigger('SORT MAITRE');
                    $entityManager->remove($trigger);
                    break;
            }

            $entityManager->flush();

            $this->addFlash('success', 'Vos modifications ont été enregistrées.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('personnage/sort.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'participant' => $participant,
            'sorts' => $sorts,
            'niveau' => $niveau,
        ]);
    }

    /**
     * Detail d'un sort.
     */
    public function sortDetailAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, Sort $sort)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->isKnownSort($sort)) {
            $this->addFlash('error', 'Vous ne connaissez pas ce sort !');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('public/sort/detail.twig', [
            'sort' => $sort,
            'participant' => $participant,
            'filename' => $sort->getPrintLabel(),
        ]);
    }

    /**
     * Obtenir le document lié à un sort.
     */
    public function sortDocumentAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, Sort $sort)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->isKnownSort($sort)) {
            $this->addFlash('error', 'Vous ne connaissez pas ce sort !');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        $file = __DIR__.'/../../../private/doc/'.$sort->getDocumentUrl();

        return $app->sendFile($file)
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $sort->getPrintLabel().'.pdf');
    }

    /**
     * Detail d'une connaissance.
     */
    public function connaissanceDetailAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, Connaissance $connaissance)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->isKnownConnaissance($connaissance)) {
            $this->addFlash('error', 'Vous ne connaissez pas cette connaissance !');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('public/connaissance/detail.twig', [
            'connaissance' => $connaissance,
            'participant' => $participant,
            'filename' => $connaissance->getPrintLabel(),
        ]);
    }

    /**
     * Obtenir le document lié à une connaissance.
     */
    public function connaissanceDocumentAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, Connaissance $connaissance)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->isKnownConnaissance($connaissance)) {
            $this->addFlash('error', 'Vous ne connaissez pas cette connaissance !');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        $file = __DIR__.'/../../../private/doc/'.$connaissance->getDocumentUrl();

        return $app->sendFile($file)
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $connaissance->getPrintLabel().'.pdf');
    }

    /**
     * Découverte de la magie, des domaines et sortilèges.
     */
    public function magieAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        $domaines = $entityManager->getRepository('\\'.\App\Entity\Domaine::class)->findAll();

        return $this->render('public/magie/index.twig', [
            'domaines' => $domaines,
            'personnage' => $personnage,
            'participant' => $participant,
        ]);
    }

    /**
     * Liste des compétences pour les joueurs.
     */
    public function competenceListAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $competences = $app['larp.manager']->getRootCompetences();

        return $this->render('public/competence/list.twig', [
            'competences' => $competences,
            'participant' => $participant,
        ]);
    }

    /**
     * Detail d'une competence.
     */
    public function competenceDetailAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, Competence $competence)
    {
        $personnage = $participant->getPersonnage();
        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->isKnownCompetence($competence)) {
            $this->addFlash('error', 'Vous ne connaissez pas cette compétence !');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('public/competence/detail.twig', [
            'competence' => $competence,
            'participant' => $participant,
            'filename' => $competence->getPrintLabel(),
        ]);
    }

    /**
     * Detail d'un document.
     */
    public function documentDetailAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, Document $document)
    {
        $personnage = $participant->getPersonnage();
        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->isKnownDocument($document)) {
            $this->addFlash('error', 'Vous ne connaissez pas ce document !');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('public/document/detail.twig', [
            'document' => $document,
            'participant' => $participant,
            'filename' => $document->getPrintLabel(),
        ]);
    }

    /**
     * Liste des classes pour le joueur.
     */
    public function classeListAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\Classe::class);
        $classes = $repo->findAllOrderedByLabel();

        return $this->render('public/classe/list.twig', [
            'classes' => $classes,
            'participant' => $participant,
        ]);
    }

    /**
     * Obtenir le document lié à une competence.
     */
    public function competenceDocumentAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, Competence $competence)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->isKnownCompetence($competence)) {
            $this->addFlash('error', 'Vous ne connaissez pas cette compétence !');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        $file = __DIR__.'/../../../private/doc/'.$competence->getDocumentUrl();

        return $app->sendFile($file)
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $competence->getPrintLabel().'.pdf');
    }

    /**
     * Obtenir le document lié à un document.
     */
    public function documentDocumentAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, Document $document)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->isKnownDocument($document)) {
            $this->addFlash('error', 'Vous ne connaissez pas ce document !');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        $file = __DIR__.'/../../../private/documents/'.$document->getDocumentUrl();

        return $app->sendFile($file)
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $document->getPrintLabel().'.pdf');
    }

    /**
     * Liste des groupes secondaires public (pour les joueurs).
     */
    public function groupeSecondaireListAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $repo = $entityManager->getRepository('\\'.SecondaryGroup::class);
        $groupeSecondaires = $repo->findAllPublic();

        return $this->render('public/groupeSecondaire/list.twig', [
            'groupeSecondaires' => $groupeSecondaires,
            'participant' => $participant,
        ]);
    }

    /**
     * Postuler à un groupe secondaire.
     */
    public function groupeSecondairePostulerAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, SecondaryGroup $groupeSecondaire)
    {
        /**
         * L'utilisateur doit avoir un personnage.
         *
         * @var Personnage $personnage
         */
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage avant de postuler à un groupe secondaire!');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        /*
         * Si le joueur est déjà postulant dans ce groupe, refUser la demande
         */
        if ($groupeSecondaire->isPostulant($personnage)) {
            $this->addFlash('error', 'Vous avez déjà postulé dans ce groupe. Inutile d\'en refaire la demande.');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        /*
         * Si le joueur est déjà membre de ce groupe, refUser la demande
         */
        if ($groupeSecondaire->isMembre($personnage)) {
            $this->addFlash('error', 'Votre êtes déjà membre de ce groupe. Inutile d\'en refaire la demande.');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        /**
         * Création du formulaire.
         *
         * @var unknown $form
         */
        $form = $this->createForm(GroupeSecondairePostulerForm::class)
            ->add('postuler', SubmitType::class, ['label' => 'Postuler']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if (empty($data['explanation'])) {
                $this->addFlash('error', 'Vos devez remplir le champ Explication.');
            } else {
                $postulant = new Postulant();
                $postulant->setPersonnage($personnage);
                $postulant->setSecondaryGroup($groupeSecondaire);
                $postulant->setExplanation($data['explanation']);
                $postulant->setWaiting(false);

                $entityManager->persist($postulant);
                $entityManager->flush();

                // envoi d'un mail au chef du groupe secondaire
                if ($groupeSecondaire->getResponsable()) {
                    // envoyer une notification au responsable
                    $app['notify']->joinGroupeSecondaire($groupeSecondaire->getResponsable(), $groupeSecondaire);
                }

                $this->addFlash('success', 'Votre candidature a été enregistrée, et transmise au chef de groupe.');

                return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
            }
        }

        return $this->render('public/groupeSecondaire/postuler.twig', [
            'groupeSecondaire' => $groupeSecondaire,
            'participant' => $participant,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affichage à destination d'un membre du groupe secondaire.
     */
    public function groupeSecondaireDetailAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, SecondaryGroup $groupeSecondaire)
    {
        $personnage = $participant->getPersonnage();
        $membre = $personnage->getMembre($groupeSecondaire);

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$membre) {
            $this->addFlash('error', 'Votre n\'êtes pas membre de ce groupe.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('public/groupeSecondaire/detail.twig', [
            'groupeSecondaire' => $groupeSecondaire,
            'membre' => $membre,
            'participant' => $participant,
        ]);
    }

    /**
     * Accepter une candidature à un groupe secondaire.
     */
    public function groupeSecondaireAcceptAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, SecondaryGroup $groupeSecondaire, Postulant $postulant)
    {
        $form = $this->createForm()
            ->add('envoyer', SubmitType::class, ['label' => 'Accepter le postulant']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $postulant->getPersonnage();

            $membre = new \App\Entity\Membre();
            $membre->setPersonnage($personnage);
            $membre->setSecondaryGroup($groupeSecondaire);
            $membre->setSecret(false);

            $entityManager->persist($membre);
            $entityManager->remove($postulant);
            $entityManager->flush();

            // envoyer une notification au nouveau membre
            $app['notify']->acceptGroupeSecondaire($personnage->getUser(), $groupeSecondaire);

            $this->addFlash('success', 'Vous avez accepté la candidature. Un message a été envoyé au joueur concerné.');

            return $this->redirectToRoute('participant.groupeSecondaire.detail', ['participant' => $participant->getId(), 'groupeSecondaire' => $groupeSecondaire->getId()], 303);
        }

        return $this->render('public/groupeSecondaire/gestion_accept.twig', [
            'groupeSecondaire' => $groupeSecondaire,
            'postulant' => $postulant,
            'participant' => $participant,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Accepter une candidature à un groupe secondaire.
     */
    public function groupeSecondaireRejectAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, SecondaryGroup $groupeSecondaire, Postulant $postulant)
    {
        $form = $this->createForm()
            ->add('envoyer', SubmitType::class, ['label' => 'RefUser le postulant']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $postulant->getPersonnage();
            $entityManager->remove($postulant);
            $entityManager->flush();

            $app['notify']->rejectGroupeSecondaire($personnage->getUser(), $groupeSecondaire);

            $this->addFlash('success', 'Vous avez refusé la candidature. Un message a été envoyé au joueur concerné.');

            return $this->redirectToRoute('participant.groupeSecondaire.detail', ['participant' => $participant->getId(), 'groupeSecondaire' => $groupeSecondaire->getId()], 303);
        }

        return $this->render('public/groupeSecondaire/gestion_reject.twig', [
            'groupeSecondaire' => $groupeSecondaire,
            'postulant' => $postulant,
            'participant' => $participant,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Laisser la candidature dans les postulant.
     */
    public function groupeSecondaireWaitAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, SecondaryGroup $groupeSecondaire, Postulant $postulant)
    {
        $form = $this->createForm()
            ->add('envoyer', SubmitType::class, ['label' => 'Laisser en attente']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $postulant->getPersonnage();
            $postulant->setWaiting(true);
            $entityManager->persist($postulant);
            $entityManager->flush($postulant);

            $app['notify']->waitGroupeSecondaire($personnage->getUser(), $groupeSecondaire);

            $this->addFlash('success', 'La candidature reste en attente. Un message a été envoyé au joueur concerné.');

            return $this->redirectToRoute('participant.groupeSecondaire.detail', ['participant' => $participant->getId(), 'groupeSecondaire' => $groupeSecondaire->getId()], 303);
        }

        return $this->render('public/groupeSecondaire/gestion_wait.twig', [
            'groupeSecondaire' => $groupeSecondaire,
            'postulant' => $postulant,
            'participant' => $participant,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Répondre à un postulant.
     */
    public function groupeSecondaireResponseAction(Request $request, EntityManagerInterface $entityManager, Participant $participant, SecondaryGroup $groupeSecondaire, Postulant $postulant)
    {
        $message = new Message();

        $message->setUserRelatedByAuteur($this->getUser());
        $message->setUserRelatedByDestinataire($postulant->getPersonnage()->getUser());
        $message->setCreationDate(new \DateTime('NOW'));
        $message->setUpdateDate(new \DateTime('NOW'));

        $form = $this->createForm(MessageForm::class, $message)
            ->add('envoyer', SubmitType::class, ['label' => 'Envoyer votre réponse']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message = $form->getData();

            $entityManager->persist($message);
            $entityManager->flush();

            $app['notify']->newMessage($postulant->getPersonnage()->getUser(), $message);

            $this->addFlash('success', 'Votre message a été envoyé au joueur concerné.');

            return $this->redirectToRoute('participant.groupeSecondaire.detail', ['participant' => $participant->getId(), 'groupeSecondaire' => $groupeSecondaire->getId()], 303);
        }

        return $this->render('public/groupeSecondaire/gestion_response.twig', [
            'groupeSecondaire' => $groupeSecondaire,
            'postulant' => $postulant,
            'participant' => $participant,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute une compétence au personnage.
     */
    public function competenceAddAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (true == $participant->getGroupeGn()->getGroupe()->getLock()) {
            $this->addFlash('error', 'Désolé, il n\'est plus possible de modifier ce personnage. Le groupe est verouillé. Contactez votre scénariste si vous pensez que cela est une erreur');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        $availableCompetences = $app['personnage.manager']->getAvailableCompetences($personnage);

        if (0 == $availableCompetences->count()) {
            $this->addFlash('error', 'Désolé, il n\'y a plus de compétence disponible (Bravo !).');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        // construit le tableau de choix
        $choices = [];
        foreach ($availableCompetences as $competence) {
            $choices[$competence->getId()] = $competence->getLabel().' (cout : '.$app['personnage.manager']->getCompetenceCout($personnage, $competence).' xp)';
        }

        $form = $this->createForm()
            ->add('competenceId', 'choice', [
                'label' => 'Choisissez une nouvelle compétence',
                'choices' => $choices,
            ])
            ->add('save', SubmitType::class, ['label' => 'Valider la compétence']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $competenceId = $data['competenceId'];
            $competence = $entityManager->find('\\'.Competence::class, $competenceId);

            $cout = $app['personnage.manager']->getCompetenceCout($personnage, $competence);
            $xp = $personnage->getXp();

            if ($xp - $cout < 0) {
                $this->addFlash('error', 'Vos n\'avez pas suffisement de points d\'expérience pour acquérir cette compétence.');

                return $this->redirectToRoute('homepage', [], 303);
            }

            $personnage->setXp($xp - $cout);
            $personnage->addCompetence($competence);
            $competence->addPersonnage($personnage);

            // cas special noblesse
            // noblesse apprentit +2 renomme
            // noblesse initie  +3 renomme
            // noblesse expert +2 renomme
            // TODO : trouver un moyen pour ne pas implémenter les règles spéciales de ce type dans le code.
            if ('Noblesse' == $competence->getCompetenceFamily()->getLabel()) {
                switch ($competence->getLevel()->getId()) {
                    case 1:
                        $personnage->addRenomme(2);
                        $renomme_history = new \App\Entity\RenommeHistory();

                        $renomme_history->setRenomme(2);
                        $renomme_history->setExplication('Compétence Noblesse niveau 1');
                        $renomme_history->setPersonnage($personnage);
                        $entityManager->persist($renomme_history);
                        break;
                    case 2:
                        $personnage->addRenomme(3);
                        $renomme_history = new \App\Entity\RenommeHistory();

                        $renomme_history->setRenomme(3);
                        $renomme_history->setExplication('Compétence Noblesse niveau 2');
                        $renomme_history->setPersonnage($personnage);
                        $entityManager->persist($renomme_history);
                        break;
                    case 3:
                        $personnage->addRenomme(2);
                        $renomme_history = new \App\Entity\RenommeHistory();

                        $renomme_history->setRenomme(2);
                        $renomme_history->setExplication('Compétence Noblesse niveau 3');
                        $renomme_history->setPersonnage($personnage);
                        $entityManager->persist($renomme_history);
                        break;
                    case 4:
                        $personnage->addRenomme(5);
                        $renomme_history = new \App\Entity\RenommeHistory();

                        $renomme_history->setRenomme(5);
                        $renomme_history->setExplication('Compétence Noblesse niveau 4');
                        $renomme_history->setPersonnage($personnage);
                        $entityManager->persist($renomme_history);
                        break;
                    case 5:
                        $personnage->addRenomme(6);
                        $renomme_history = new \App\Entity\RenommeHistory();

                        $renomme_history->setRenomme(6);
                        $renomme_history->setExplication('Compétence Noblesse niveau 5');
                        $renomme_history->setPersonnage($personnage);
                        $entityManager->persist($renomme_history);
                        break;
                }
            }

            // cas special prêtrise
            if ('Prêtrise' == $competence->getCompetenceFamily()->getLabel()) {
                // le personnage doit avoir une religion au niveau fervent ou fanatique
                if ($personnage->isFervent() || $personnage->isFanatique()) {
                    // ajoute toutes les prières de niveau de sa compétence liés aux sphère de sa religion fervente ou fanatique
                    $religion = $personnage->getMainReligion();
                    foreach ($religion->getSpheres() as $sphere) {
                        foreach ($sphere->getPrieres() as $priere) {
                            if ($priere->getNiveau() == $competence->getLevel()->getId() && !$personnage->hasPriere($priere)) {
                                $priere->addPersonnage($personnage);
                                $personnage->addPriere($priere);
                            }
                        }
                    }
                } else {
                    $this->addFlash('error', 'Pour obtenir la compétence Prêtrise, vous devez être FERVENT ou FANATIQUE');

                    return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
                }
            }

            // case special prêtrise initié
            if ('Prêtrise' == $competence->getCompetenceFamily()->getLabel() && $competence->getLevel()->getId() >= 2) {
                $trigger = new \App\Entity\PersonnageTrigger();
                $trigger->setPersonnage($personnage);
                $trigger->setTag('PRETISE INITIE');
                $trigger->setDone(false);
                $entityManager->persist($trigger);
                $trigger2 = new \App\Entity\PersonnageTrigger();
                $trigger2->setPersonnage($personnage);
                $trigger2->setTag('PRETISE INITIE');
                $trigger2->setDone(false);
                $entityManager->persist($trigger2);
                $trigger3 = new \App\Entity\PersonnageTrigger();
                $trigger3->setPersonnage($personnage);
                $trigger3->setTag('PRETISE INITIE');
                $trigger3->setDone(false);
                $entityManager->persist($trigger3);
                $entityManager->flush();
            }

            // cas special alchimie
            if ('Alchimie' == $competence->getCompetenceFamily()->getLabel()) {
                switch ($competence->getLevel()->getId()) {
                    case 1: // le personnage doit choisir 2 potions de niveau apprenti
                        $trigger = new \App\Entity\PersonnageTrigger();
                        $trigger->setPersonnage($personnage);
                        $trigger->setTag('ALCHIMIE APPRENTI');
                        $trigger->setDone(false);
                        $entityManager->persist($trigger);

                        $trigger2 = new \App\Entity\PersonnageTrigger();
                        $trigger2->setPersonnage($personnage);
                        $trigger2->setTag('ALCHIMIE APPRENTI');
                        $trigger2->setDone(false);
                        $entityManager->persist($trigger2);
                        $entityManager->flush();
                        break;
                    case 2: // le personnage doit choisir 1 potion de niveau initie et 1 potion de niveau apprenti
                        $trigger = new \App\Entity\PersonnageTrigger();
                        $trigger->setPersonnage($personnage);
                        $trigger->setTag('ALCHIMIE INITIE');
                        $trigger->setDone(false);
                        $entityManager->persist($trigger);

                        $trigger2 = new \App\Entity\PersonnageTrigger();
                        $trigger2->setPersonnage($personnage);
                        $trigger2->setTag('ALCHIMIE APPRENTI');
                        $trigger2->setDone(false);
                        $entityManager->persist($trigger2);
                        $entityManager->flush();
                        break;
                    case 3: // le personnage doit choisir 1 potion de niveau expert
                        $trigger = new \App\Entity\PersonnageTrigger();
                        $trigger->setPersonnage($personnage);
                        $trigger->setTag('ALCHIMIE EXPERT');
                        $trigger->setDone(false);
                        $entityManager->persist($trigger);
                        $entityManager->flush();
                        break;
                    case 4: // le personnage doit choisir 1 potion de niveau maitre
                        $trigger = new \App\Entity\PersonnageTrigger();
                        $trigger->setPersonnage($personnage);
                        $trigger->setTag('ALCHIMIE MAITRE');
                        $trigger->setDone(false);
                        $entityManager->persist($trigger);
                        $entityManager->flush();
                        break;
                }
            }

            // cas special magie
            if ('Magie' == $competence->getCompetenceFamily()->getLabel()) {
                switch ($competence->getLevel()->getId()) {
                    case 1: // le personnage doit choisir un domaine de magie
                        $trigger = new \App\Entity\PersonnageTrigger();
                        $trigger->setPersonnage($personnage);
                        $trigger->setTag('DOMAINE MAGIE');
                        $trigger->setDone(false);
                        $entityManager->persist($trigger);

                        // il obtient aussi la possibilité de choisir un sort de niveau 1
                        $trigger2 = new \App\Entity\PersonnageTrigger();
                        $trigger2->setPersonnage($personnage);
                        $trigger2->setTag('SORT APPRENTI');
                        $trigger2->setDone(false);
                        $entityManager->persist($trigger2);
                        $entityManager->flush();
                        break;
                    case 2:
                        // il obtient aussi la possibilité de choisir un sort de niveau 2
                        $trigger = new \App\Entity\PersonnageTrigger();
                        $trigger->setPersonnage($personnage);
                        $trigger->setTag('SORT INITIE');
                        $trigger->setDone(false);
                        $entityManager->persist($trigger);
                        $entityManager->flush();
                        break;
                    case 3: // le personnage peut choisir un nouveau domaine de magie
                        $trigger = new \App\Entity\PersonnageTrigger();
                        $trigger->setPersonnage($personnage);
                        $trigger->setTag('DOMAINE MAGIE');
                        $trigger->setDone(false);
                        $entityManager->persist($trigger);

                        // il obtient aussi la possibilité de choisir un sort de niveau 3
                        $trigger2 = new \App\Entity\PersonnageTrigger();
                        $trigger2->setPersonnage($personnage);
                        $trigger2->setTag('SORT EXPERT');
                        $trigger2->setDone(false);
                        $entityManager->persist($trigger2);
                        $entityManager->flush();
                        break;
                    case 4:
                        // il obtient aussi la possibilité de choisir un sort de niveau 4
                        $trigger = new \App\Entity\PersonnageTrigger();
                        $trigger->setPersonnage($personnage);
                        $trigger->setTag('SORT MAITRE');
                        $trigger->setDone(false);
                        $entityManager->persist($trigger);
                        $entityManager->flush();
                        break;
                }
            }

            // cas special artisanat
            if ('Artisanat' == $competence->getCompetenceFamily()->getLabel()) {
                switch ($competence->getLevel()->getId()) {
                    case 1:
                        break;
                    case 2:
                        break;
                    case 3: // le personnage doit choisir 1 technologie
                    case 4: // le personnage doit choisir 1 technologie
                        $trigger = new \App\Entity\PersonnageTrigger();
                        $trigger->setPersonnage($personnage);
                        $trigger->setTag('TECHNOLOGIE');
                        $trigger->setDone(false);
                        $entityManager->persist($trigger);
                        $entityManager->flush();
                        break;
                }
            }

            // cas special littérature
            if ('Littérature' == $competence->getCompetenceFamily()->getLabel()) {
                switch ($competence->getLevel()->getId()) {
                    case 1: // 2 langues commune supplémentaires de son choix
                        $trigger = new \App\Entity\PersonnageTrigger();
                        $trigger->setPersonnage($personnage);
                        $trigger->setTag('LANGUE COURANTE');
                        $trigger->setDone(false);
                        $entityManager->persist($trigger);

                        $trigger2 = new \App\Entity\PersonnageTrigger();
                        $trigger2->setPersonnage($personnage);
                        $trigger2->setTag('LANGUE COURANTE');
                        $trigger2->setDone(false);
                        $entityManager->persist($trigger2);
                        $entityManager->flush();

                        break;
                    case 2: //  Sait parler, lire et écrire trois autres langues vivantes (courante ou commune) de son choix.
                        $trigger = new \App\Entity\PersonnageTrigger();
                        $trigger->setPersonnage($personnage);
                        $trigger->setTag('LANGUE COURANTE');
                        $trigger->setDone(false);
                        $entityManager->persist($trigger);

                        $trigger2 = new \App\Entity\PersonnageTrigger();
                        $trigger2->setPersonnage($personnage);
                        $trigger2->setTag('LANGUE COURANTE');
                        $trigger2->setDone(false);
                        $entityManager->persist($trigger2);

                        $trigger3 = new \App\Entity\PersonnageTrigger();
                        $trigger3->setPersonnage($personnage);
                        $trigger3->setTag('LANGUE COURANTE');
                        $trigger3->setDone(false);
                        $entityManager->persist($trigger3);

                        // il obtient aussi la possibilité de choisir un sort de niveau 1
                        $trigger4 = new \App\Entity\PersonnageTrigger();
                        $trigger4->setPersonnage($personnage);
                        $trigger4->setTag('SORT APPRENTI');
                        $trigger4->setDone(false);
                        $entityManager->persist($trigger4);

                        $trigger5 = new \App\Entity\PersonnageTrigger();
                        $trigger5->setPersonnage($personnage);
                        $trigger5->setTag('ALCHIMIE APPRENTI');
                        $trigger5->setDone(false);
                        $entityManager->persist($trigger5);
                        $entityManager->flush();

                        break;
                    case 3: // Sait parler, lire et écrire un langage ancien ainsi que trois autres langues vivantes (courante ou commune) de son choix ainsi qu'une langue ancienne
                        $trigger = new \App\Entity\PersonnageTrigger();
                        $trigger->setPersonnage($personnage);
                        $trigger->setTag('LANGUE COURANTE');
                        $trigger->setDone(false);
                        $entityManager->persist($trigger);

                        $trigger2 = new \App\Entity\PersonnageTrigger();
                        $trigger2->setPersonnage($personnage);
                        $trigger2->setTag('LANGUE COURANTE');
                        $trigger2->setDone(false);
                        $entityManager->persist($trigger2);

                        $trigger3 = new \App\Entity\PersonnageTrigger();
                        $trigger3->setPersonnage($personnage);
                        $trigger3->setTag('LANGUE COURANTE');
                        $trigger3->setDone(false);
                        $entityManager->persist($trigger3);

                        $trigger4 = new \App\Entity\PersonnageTrigger();
                        $trigger4->setPersonnage($personnage);
                        $trigger4->setTag('LANGUE ANCIENNE');
                        $trigger4->setDone(false);
                        $entityManager->persist($trigger4);

                        // il obtient aussi la possibilité de choisir un sort et une potion de niveau 2
                        $trigger5 = new \App\Entity\PersonnageTrigger();
                        $trigger5->setPersonnage($personnage);
                        $trigger5->setTag('SORT INITIE');
                        $trigger5->setDone(false);
                        $entityManager->persist($trigger5);

                        $trigger6 = new \App\Entity\PersonnageTrigger();
                        $trigger6->setPersonnage($personnage);
                        $trigger6->setTag('ALCHIMIE INITIE');
                        $trigger6->setDone(false);
                        $entityManager->persist($trigger6);
                        $entityManager->flush();
                        break;
                    case 4: // Sait parler, lire et écrire un autre langage ancien ainsi que trois autres langues vivantes de son choix (courante ou commune) ainsi qu'une langue ancienne
                        $trigger = new \App\Entity\PersonnageTrigger();
                        $trigger->setPersonnage($personnage);
                        $trigger->setTag('LANGUE COURANTE');
                        $trigger->setDone(false);
                        $entityManager->persist($trigger);

                        $trigger2 = new \App\Entity\PersonnageTrigger();
                        $trigger2->setPersonnage($personnage);
                        $trigger2->setTag('LANGUE COURANTE');
                        $trigger2->setDone(false);
                        $entityManager->persist($trigger2);

                        $trigger3 = new \App\Entity\PersonnageTrigger();
                        $trigger3->setPersonnage($personnage);
                        $trigger3->setTag('LANGUE COURANTE');
                        $trigger3->setDone(false);
                        $entityManager->persist($trigger3);

                        $trigger4 = new \App\Entity\PersonnageTrigger();
                        $trigger4->setPersonnage($personnage);
                        $trigger4->setTag('LANGUE ANCIENNE');
                        $trigger4->setDone(false);
                        $entityManager->persist($trigger4);

                        // il obtient aussi la possibilité de choisir un sort et une potion de niveau 3
                        $trigger5 = new \App\Entity\PersonnageTrigger();
                        $trigger5->setPersonnage($personnage);
                        $trigger5->setTag('SORT EXPERT');
                        $trigger5->setDone(false);
                        $entityManager->persist($trigger5);

                        $trigger6 = new \App\Entity\PersonnageTrigger();
                        $trigger6->setPersonnage($personnage);
                        $trigger6->setTag('ALCHIMIE EXPERT');
                        $trigger6->setDone(false);
                        $entityManager->persist($trigger6);
                        $entityManager->flush();
                        break;
                }
            }

            // historique
            $historique = new \App\Entity\ExperienceUsage();
            $historique->setOperationDate(new \DateTime('NOW'));
            $historique->setXpUse($cout);
            $historique->setCompetence($competence);
            $historique->setPersonnage($personnage);

            $entityManager->persist($competence);
            $entityManager->persist($personnage);
            $entityManager->persist($historique);
            $entityManager->flush();

            $this->addFlash('success', 'Votre personnage a été sauvegardé.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('public/personnage/competence.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'competences' => $availableCompetences,
            'participant' => $participant,
        ]);
    }

    /**
     * Affiche le formulaire d'ajout d'un joueur.
     */
    public function addAction(Request $request, EntityManagerInterface $entityManager)
    {
        $joueur = new \App\Entity\Joueur();

        $form = $this->createForm(JoueurForm::class, $joueur)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

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
     * Recherche d'un joueur.
     */
    public function searchAction(Request $request, EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(FindJoueurForm::class, [])
            ->add('submit', SubmitType::class, ['label' => 'Rechercher']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $type = $data['type'];
            $search = $data['search'];

            $repo = $entityManager->getRepository('\App\Entity\Joueur');

            $joueurs = null;

            switch ($type) {
                case 'lastName':
                    $joueurs = $repo->findByLastName($search);
                    break;
                case 'firstName':
                    $joueurs = $repo->findByFirstName($search);
                    break;
                case 'numero':
                    // TODO
                    break;
            }

            if (null != $joueurs) {
                if (0 == $joueurs->count()) {
                    $this->addFlash('error', 'Le joueur n\'a pas été trouvé.');

                    return $this->redirectToRoute('homepage', [], 303);
                } elseif (1 == $joueurs->count()) {
                    $this->addFlash('success', 'Le joueur a été trouvé.');

                    return $this->redirectToRoute('joueur.detail.orga', ['index' => $joueurs->first()->getId()], 303);
                } else {
                    $this->addFlash('success', 'Il y a plusieurs résultats à votre recherche.');

                    return $this->render('joueur/search_result.twig', [
                        'joueurs' => $joueurs,
                    ]);
                }
            }

            $this->addFlash('error', 'Désolé, le joueur n\'a pas été trouvé.');
        }

        return $this->render('joueur/search.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Detail d'un joueur.
     */
    public function adminDetailAction(Request $request, EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $joueur = $entityManager->find('\App\Entity\Joueur', $id);

        if ($joueur) {
            return $this->render('joueur/admin/detail.twig', ['joueur' => $joueur]);
        } else {
            $this->addFlash('error', 'Le joueur n\'a pas été trouvé.');

            return $this->redirectToRoute('homepage');
        }
    }

    /**
     * Met a jours les points d'expérience des joueurs.
     */
    public function adminXpAction(EntityManagerInterface $entityManager, Request $request)
    {
        $id = $request->get('index');

        $joueur = $entityManager->find('\App\Entity\Joueur', $id);

        $form = $this->createForm(JoueurXpForm::class, $joueur)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ('POST' == $request->getMethod()) {
            $newXps = $request->get('xp');
            $explanation = $request->get('explanation');

            $personnage = $joueur->getPersonnage();
            if ($personnage->getXp() != $newXps) {
                $oldXp = $personnage->getXp();
                $gain = $newXps - $oldXp;

                $personnage->setXp($newXps);
                $entityManager->persist($personnage);

                // historique
                $historique = new \App\Entity\ExperienceGain();
                $historique->setExplanation($explanation);
                $historique->setOperationDate(new \DateTime('NOW'));
                $historique->setPersonnage($personnage);
                $historique->setXpGain($gain);
                $entityManager->persist($historique);
                $entityManager->flush();

                $this->addFlash('success', 'Les points d\'expérience ont été sauvegardés');
            }
        }

        return $this->render('joueur/admin/xp.twig', [
            'joueur' => $joueur,
        ]);
    }

    /**
     * Detail d'un joueur (pour les orgas).
     */
    public function detailOrgaAction(Request $request, EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $joueur = $entityManager->find('\App\Entity\Joueur', $id);

        if ($joueur) {
            return $this->render('joueur/admin/detail.twig', ['joueur' => $joueur]);
        } else {
            $this->addFlash('error', 'Le joueur n\'a pas été trouvé.');

            return $this->redirectToRoute('homepage');
        }
    }

    /**
     * Met à jour les informations d'un joueur.
     */
    public function updateAction(Request $request, EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $joueur = $entityManager->find('\App\Entity\Joueur', $id);

        $form = $this->createForm(JoueurForm::class, $joueur)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder']);

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
     * Demander une nouvelle alliance.
     */
    public function requestAllianceAction(Request $request, EntityManagerInterface $entityManager)
    {
        $participant = $request->get('participant');
        $groupe = $request->get('groupe');
        $groupeGn = $participant->getGroupeGn();

        if (true == $groupe->getLock()) {
            $this->addFlash('error', 'Les relations diplomatiques entre pays sont actuellement gelées jusqu’au GN (pour que nous puissions avoir un état de la situation). Vous pourrez les modifier en jeu désormais (voir le jeu diplomatique)');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        // un groupe ne peux pas avoir plus de 3 alliances
        if ($groupe->getAlliances()->count() >= 3) {
            $this->addFlash('error', 'Désolé, vous avez déjà 3 alliances, ce qui est le maximum possible.');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        // un groupe ne peux pas avoir plus d'alliances que d'ennemis
        if ($groupe->getEnnemies()->count() - $groupe->getAlliances()->count() <= 0) {
            $this->addFlash('error', 'Désolé, vous n\'avez pas suffisement d\'ennemis pour pouvoir vous choisir un allié.');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        $alliance = new \App\Entity\GroupeAllie();
        $alliance->setGroupe($groupe);

        $form = $this->createForm(RequestAllianceForm::class, $alliance)
            ->add('send', SubmitType::class, ['label' => 'Envoyer']);

        $form->handleRequest($request);

        if ($request->isMethod('POST')) {
            $alliance = $form->getData();
            $alliance->setGroupeAccepted(true);
            $alliance->setGroupeAllieAccepted(false);

            // vérification des conditions pour le groupe choisi
            $requestedGroupe = $alliance->getRequestedGroupe();
            if ($requestedGroupe == $groupe) {
                $this->addFlash('error', 'Désolé, vous ne pouvez pas choisir votre propre groupe pour faire une alliance ...');

                return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
            }

            if ($groupe->isAllyTo($requestedGroupe)) {
                $this->addFlash('error', 'Désolé, vous êtes déjà allié avec ce groupe');

                return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
            }

            if ($groupe->isEnemyTo($requestedGroupe)) {
                $this->addFlash('error', 'Désolé, vous êtes ennemi avec ce groupe. Impossible de faire une alliance, faites d\'abord la paix !');

                return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
            }

            if ($requestedGroupe->getAlliances()->count() >= 3) {
                $this->addFlash('error', 'Désolé, le groupe demandé dispose déjà de 3 alliances, ce qui est le maximum possible.');

                return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
            }

            if ($requestedGroupe->getEnnemies()->count() - $requestedGroupe->getAlliances()->count() <= 0) {
                $this->addFlash('error', 'Désolé, le groupe demandé n\'a pas suffisement d\'ennemis pour pouvoir obtenir un allié supplémentaire.');

                return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
            }

            $entityManager->persist($alliance);
            $entityManager->flush();

            $app['User.mailer']->sendRequestAlliance($alliance);

            $this->addFlash('success', 'Votre demande a été envoyée.');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        return $this->render('public/groupe/requestAlliance.twig', [
            'groupe' => $groupe,
            'participant' => $participant,
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Annuler une demande d'alliance.
     */
    public function cancelRequestedAllianceAction(Request $request, EntityManagerInterface $entityManager)
    {
        $participant = $request->get('participant');
        $groupe = $request->get('groupe');
        $alliance = $request->get('alliance');
        $groupeGn = $participant->getGroupeGn();

        if (true == $groupe->getLock()) {
            $this->addFlash('error', 'Les relations diplomatiques entre pays sont actuellement gelées jusqu’au GN (pour que nous puissions avoir un état de la situation). Vous pourrez les modifier en jeu désormais (voir le jeu diplomatique)');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        $form = $this->createForm(CancelRequestedAllianceForm::class, $alliance)
            ->add('send', SubmitType::class, ['label' => "Oui, j'annule ma demande"]);

        $form->handleRequest($request);

        if ($request->isMethod('POST')) {
            $alliance = $form->getData();

            $entityManager->remove($alliance);
            $entityManager->flush();

            $app['User.mailer']->sendCancelAlliance($alliance);

            $this->addFlash('success', 'Votre demande d\'alliance a été annulée.');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        return $this->render('public/groupe/cancelAlliance.twig', [
            'alliance' => $alliance,
            'groupe' => $groupe,
            'participant' => $participant,
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Accepter une alliance.
     */
    public function acceptAllianceAction(Request $request, EntityManagerInterface $entityManager)
    {
        $participant = $request->get('participant');
        $groupe = $request->get('groupe');
        $alliance = $request->get('alliance');
        $groupeGn = $participant->getGroupeGn();

        if (true == $groupe->getLock()) {
            $this->addFlash('error', 'Les relations diplomatiques entre pays sont actuellement gelées jusqu’au GN (pour que nous puissions avoir un état de la situation). Vous pourrez les modifier en jeu désormais (voir le jeu diplomatique)');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        $form = $this->createForm(AcceptAllianceForm::class, $alliance)
            ->add('send', SubmitType::class, ['label' => 'Envoyer']);

        $form->handleRequest($request);

        if ($request->isMethod('POST')) {
            $alliance = $form->getData();

            $alliance->setGroupeAllieAccepted(true);
            $entityManager->persist($alliance);
            $entityManager->flush();

            $app['User.mailer']->sendAcceptAlliance($alliance);

            $this->addFlash('success', 'Vous avez accepté la proposition d\'alliance.');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        return $this->render('public/groupe/acceptAlliance.twig', [
            'alliance' => $alliance,
            'groupe' => $groupe,
            'participant' => $participant,
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * RefUser une alliance.
     */
    public function refuseAllianceAction(Request $request, EntityManagerInterface $entityManager)
    {
        $participant = $request->get('participant');
        $groupe = $request->get('groupe');
        $alliance = $request->get('alliance');
        $groupeGn = $participant->getGroupeGn();

        if (true == $groupe->getLock()) {
            $this->addFlash('error', 'Les relations diplomatiques entre pays sont actuellement gelées jusqu’au GN (pour que nous puissions avoir un état de la situation). Vous pourrez les modifier en jeu désormais (voir le jeu diplomatique)');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        $form = $this->createForm(RefuseAllianceForm::class, $alliance)
            ->add('send', SubmitType::class, ['label' => 'Envoyer']);

        $form->handleRequest($request);

        if ($request->isMethod('POST')) {
            $alliance = $form->getData();

            $entityManager->remove($alliance);
            $entityManager->flush();

            $app['User.mailer']->sendRefuseAlliance($alliance);

            $this->addFlash('success', 'Vous avez refusé la proposition d\'alliance.');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        return $this->render('public/groupe/refuseAlliance.twig', [
            'alliance' => $alliance,
            'groupe' => $groupe,
            'participant' => $participant,
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Briser une alliance.
     */
    public function breakAllianceAction(Request $request, EntityManagerInterface $entityManager)
    {
        $participant = $request->get('participant');
        $groupe = $request->get('groupe');
        $alliance = $request->get('alliance');
        $groupeGn = $participant->getGroupeGn();

        if (true == $groupe->getLock()) {
            $this->addFlash('error', 'Les relations diplomatiques entre pays sont actuellement gelées jusqu’au GN (pour que nous puissions avoir un état de la situation). Vous pourrez les modifier en jeu désormais (voir le jeu diplomatique)');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        $form = $this->createForm(BreakAllianceForm::class, $alliance)
            ->add('send', SubmitType::class, ['label' => 'Envoyer']);

        $form->handleRequest($request);

        if ($request->isMethod('POST')) {
            $alliance = $form->getData();

            $entityManager->remove($alliance);
            $entityManager->flush();

            if ($alliance->getGroupe() == $groupe) {
                $app['User.mailer']->sendBreakAlliance($alliance, $alliance->getRequestedGroupe());
            } else {
                $app['User.mailer']->sendBreakAlliance($alliance, $groupe);
            }

            $this->addFlash('success', 'Vous avez brisé une alliance.');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        return $this->render('public/groupe/breakAlliance.twig', [
            'alliance' => $alliance,
            'groupe' => $groupe,
            'participant' => $participant,
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Déclarer la guerre.
     */
    public function declareWarAction(Request $request, EntityManagerInterface $entityManager)
    {
        $participant = $request->get('participant');
        $groupe = $request->get('groupe');
        $groupeGn = $participant->getGroupeGn();

        if (true == $groupe->getLock()) {
            $this->addFlash('error', 'Les relations diplomatiques entre pays sont actuellement gelées jusqu’au GN (pour que nous puissions avoir un état de la situation). Vous pourrez les modifier en jeu désormais (voir le jeu diplomatique)');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        // un groupe ne peux pas faire de déclaration de guerre si il a 3 ou plus ennemis
        if ($groupe->getEnnemies()->count() >= 3) {
            $this->addFlash('error', 'Désolé, vous avez déjà 3 ennemis ou plus, impossible de faire une nouvelle déclaration de guerre .');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        $war = new \App\Entity\GroupeEnemy();
        $war->setGroupe($groupe);
        $war->setGroupePeace(false);
        $war->setGroupeEnemyPeace(false);

        $form = $this->createForm(DeclareWarForm::class, $war)
            ->add('send', SubmitType::class, ['label' => 'Envoyer']);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            $war = $form->getData();
            $war->setGroupePeace(false);
            $war->setGroupeEnemyPeace(false);

            // vérification des conditions pour le groupe choisi
            $requestedGroupe = $war->getRequestedGroupe();
            if ($requestedGroupe == $groupe) {
                $this->addFlash('error', 'Désolé, vous ne pouvez pas choisir votre propre groupe comme ennemi ...');

                return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
            }

            if ($groupe->isEnemyTo($requestedGroupe)) {
                $this->addFlash('error', 'Désolé, vous êtes déjà en guerre avec ce groupe');

                return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
            }

            if ($requestedGroupe->getEnnemies()->count() >= 5) {
                $this->addFlash('error', 'Désolé, le groupe demandé dispose déjà de 5 ennemis, ce qui est le maximum possible.');

                return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
            }

            if ($groupe->isEnemyTo($requestedGroupe)) {
                $this->addFlash('error', 'Désolé, vous êtes déjà allié avec ce groupe');

                return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
            }

            $entityManager->persist($war);
            $entityManager->flush();

            $app['User.mailer']->sendDeclareWar($war);

            $this->addFlash('success', 'Votre déclaration de guerre vient d\'être envoyée.');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        return $this->render('public/groupe/declareWar.twig', [
            'groupe' => $groupe,
            'participant' => $participant,
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Demander la paix.
     */
    public function requestPeaceAction(Request $request, EntityManagerInterface $entityManager)
    {
        $participant = $request->get('participant');
        $groupe = $request->get('groupe');
        $war = $request->get('enemy');
        $groupeGn = $participant->getGroupeGn();

        if (true == $groupe->getLock()) {
            $this->addFlash('error', 'Les relations diplomatiques entre pays sont actuellement gelées jusqu’au GN (pour que nous puissions avoir un état de la situation). Vous pourrez les modifier en jeu désormais (voir le jeu diplomatique)');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        $form = $this->createForm(RequestPeaceForm::class, $war)
            ->add('send', SubmitType::class, ['label' => 'Envoyer']);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $war = $form->getData();

            if ($groupe == $war->getGroupe()) {
                $war->setGroupePeace(true);
            } else {
                $war->setGroupeEnemyPeace(true);
            }

            $entityManager->persist($war);
            $entityManager->flush();

            $app['User.mailer']->sendRequestPeace($war, $groupe);

            $this->addFlash('success', 'Votre demande de paix vient d\'être envoyée.');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        return $this->render('public/groupe/requestPeace.twig', [
            'war' => $war,
            'groupe' => $groupe,
            'participant' => $participant,
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Accepter la paix.
     */
    public function acceptPeaceAction(Request $request, EntityManagerInterface $entityManager)
    {
        $participant = $request->get('participant');
        $groupe = $request->get('groupe');
        $war = $request->get('enemy');
        $groupeGn = $participant->getGroupeGn();

        if (true == $groupe->getLock()) {
            $this->addFlash('error', 'Les relations diplomatiques entre pays sont actuellement gelées jusqu’au GN (pour que nous puissions avoir un état de la situation). Vous pourrez les modifier en jeu désormais (voir le jeu diplomatique)');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        $form = $this->createForm(AcceptPeaceForm::class, $war)
            ->add('send', SubmitType::class, ['label' => 'Envoyer']);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $war = $form->getData();

            if ($groupe == $war->getGroupe()) {
                $war->setGroupePeace(true);
            } else {
                $war->setGroupeEnemyPeace(true);
            }

            $entityManager->persist($war);
            $entityManager->flush();

            $app['User.mailer']->sendAcceptPeace($war, $groupe);

            $this->addFlash('success', 'Vous avez fait la paix !');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        return $this->render('public/groupe/acceptPeace.twig', [
            'war' => $war,
            'groupe' => $groupe,
            'participant' => $participant,
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * RefUser la paix.
     */
    public function refusePeaceAction(Request $request, EntityManagerInterface $entityManager)
    {
        $participant = $request->get('participant');
        $groupe = $request->get('groupe');
        $war = $request->get('enemy');
        $groupeGn = $participant->getGroupeGn();

        if (true == $groupe->getLock()) {
            $this->addFlash('error', 'Les relations diplomatiques entre pays sont actuellement gelées jusqu’au GN (pour que nous puissions avoir un état de la situation). Vous pourrez les modifier en jeu désormais (voir le jeu diplomatique)');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        $form = $this->createForm(RefusePeaceForm::class, $war)
            ->add('send', SubmitType::class, ['label' => 'Envoyer']);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $war = $form->getData();

            $war->setGroupePeace(false);
            $war->setGroupeEnemyPeace(false);

            $entityManager->persist($war);
            $entityManager->flush();

            $app['User.mailer']->sendRefusePeace($war, $groupe);

            $this->addFlash('success', 'Vous avez refusé la proposition de paix.');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        return $this->render('public/groupe/refusePeace.twig', [
            'war' => $war,
            'groupe' => $groupe,
            'participant' => $participant,
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Annuler la demande de paix.
     */
    public function cancelRequestedPeaceAction(Request $request, EntityManagerInterface $entityManager)
    {
        $participant = $request->get('participant');
        $groupe = $request->get('groupe');
        $war = $request->get('enemy');

        $groupeGn = $participant->getGroupeGn();

        if (true == $groupe->getLock()) {
            $this->addFlash('error', 'Les relations diplomatiques entre pays sont actuellement gelées jusqu’au GN (pour que nous puissions avoir un état de la situation). Vous pourrez les modifier en jeu désormais (voir le jeu diplomatique)');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        $form = $this->createForm(CancelRequestedPeaceForm::class, $war)
            ->add('send', SubmitType::class, ['label' => 'Envoyer']);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $war = $form->getData();

            if ($groupe == $war->getGroupe()) {
                $war->setGroupePeace(false);
            } else {
                $war->setGroupeEnemyPeace(false);
            }

            $entityManager->persist($war);
            $entityManager->flush();

            $app['User.mailer']->sendRefusePeace($war, $groupe);

            $this->addFlash('success', 'Vous avez annulé votre proposition de paix.');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        return $this->render('public/groupe/cancelPeace.twig', [
            'war' => $war,
            'groupe' => $groupe,
            'participant' => $participant,
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Choix d'une technologie.
     */
    public function technologieAction(Request $request, EntityManagerInterface $entityManager, Participant $participant)
    {
        $personnage = $participant->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir créé un personnage !');

            return $this->redirectToRoute('gn.detail', ['gn' => $participant->getGn()->getId()], 303);
        }

        if (!$personnage->hasTrigger('TECHNOLOGIE')) {
            $this->addFlash('error', 'Désolé, vous ne pouvez pas choisir de technologie supplémentaire.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        $technologies = $app['personnage.manager']->getAvailableTechnologies($personnage);

        $form = $this->createForm()
            ->add('technologies', 'entity', [
                'required' => true,
                'label' => 'Choisissez votre technologie',
                'multiple' => false,
                'expanded' => true,
                'class' => Technologie::class,
                'choices' => $technologies,
                'choice_label' => 'label',
            ])
            ->add('save', SubmitType::class, ['label' => 'Valider votre technologie']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $technologie = $data['technologies'];

            // Ajout de la technologie au personnage
            $personnage->addTechnologie($technologie);
            $entityManager->persist($personnage);

            // suppression du trigger
            $trigger = $personnage->getTrigger('TECHNOLOGIE');
            $entityManager->remove($trigger);

            $entityManager->flush();

            $this->addFlash('success', 'Vos modifications ont été enregistrées.');

            return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
        }

        return $this->render('personnage/technologie.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'participant' => $participant,
            'technologies' => $technologies,
        ]);
    }
}
