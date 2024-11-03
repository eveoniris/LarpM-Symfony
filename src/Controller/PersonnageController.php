<?php

namespace App\Controller;

use App\Entity\Competence;
use App\Entity\Connaissance;
use App\Entity\ExperienceGain;
use App\Entity\ExperienceUsage;
use App\Entity\Personnage;
use App\Entity\PersonnageChronologie;
use App\Entity\PersonnageHasToken;
use App\Entity\PersonnageIngredient;
use App\Entity\PersonnageLignee;
use App\Entity\PersonnageRessource;
use App\Entity\Potion;
use App\Entity\Priere;
use App\Entity\RenommeHistory;
use App\Entity\Sort;
use App\Entity\Technologie;
use App\Enum\FolderType;
use App\Form\Personnage\PersonnageChronologieForm;
use App\Form\Personnage\PersonnageDocumentForm;
use App\Form\Personnage\PersonnageIngredientForm;
use App\Form\Personnage\PersonnageItemForm;
use App\Form\Personnage\PersonnageLigneeForm;
use App\Form\Personnage\PersonnageOriginForm;
use App\Form\Personnage\PersonnageReligionForm;
use App\Form\Personnage\PersonnageRessourceForm;
use App\Form\Personnage\PersonnageRichesseForm;
use App\Form\Personnage\PersonnageTechnologieForm;
use App\Form\Personnage\PersonnageUpdateHeroismeForm;
use App\Form\Personnage\PersonnageUpdatePugilatForm;
use App\Form\Personnage\PersonnageUpdateRenommeForm;
use App\Form\PersonnageBackgroundForm;
use App\Form\PersonnageDeleteForm;
use App\Form\PersonnageFindForm;
use App\Form\PersonnageForm;
use App\Form\PersonnageStatutForm;
use App\Form\PersonnageUpdateAgeForm;
use App\Form\PersonnageUpdateDomaineForm;
use App\Form\PersonnageUpdateForm;
use App\Form\PersonnageXpForm;
use App\Form\PotionFindForm;
use App\Form\PriereFindForm;
use App\Form\SortFindForm;
use App\Form\TriggerDeleteForm;
use App\Form\TriggerForm;
use App\Form\TrombineForm;
use App\Manager\GroupeManager;
use App\Manager\PersonnageManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/personnage', name: 'personnage.')]
class PersonnageController extends AbstractController
{
    // contient la liste des colonnes
    protected $columnDefinitions = [
        'colId' => ['label' => '#', 'fieldName' => 'id',  'sortFieldName' => 'id', 'tooltip' => 'Numéro d\'identifiant'],
        'colStatut' => ['label' => 'S', 'fieldName' => 'status',  'sortFieldName' => 'status', 'tooltip' => 'Statut'],
        'colNom' => ['label' => 'Nom', 'fieldName' => 'nom',  'sortFieldName' => 'nom', 'tooltip' => 'Nom et surnom du personnage'],
        'colClasse' => ['label' => 'Classe', 'fieldName' => 'classe',  'sortFieldName' => 'classe', 'tooltip' => 'Classe du personnage'],
        'colGroupe' => ['label' => 'Groupe', 'fieldName' => 'groupe',  'sortFieldName' => 'groupe', 'tooltip' => 'Dernier GN - Groupe participant'],
        'colRenommee' => ['label' => 'Renommée', 'fieldName' => 'renomme',  'sortFieldName' => 'renomme', 'tooltip' => 'Points de renommée'],
        'colPugilat' => ['label' => 'Pugilat', 'fieldName' => 'pugilat',  'sortFieldName' => 'pugilat', 'tooltip' => 'Points de pugilat'],
        'colHeroisme' => ['label' => 'Héroïsme', 'fieldName' => 'heroisme',  'sortFieldName' => 'heroisme', 'tooltip' => 'Points d\'héroisme'],
        'colUser' => ['label' => 'Utilisateur', 'fieldName' => 'user',  'sortFieldName' => 'user', 'tooltip' => 'Liste des utilisateurs (Nom et prénom) par GN'],
        'colXp' => ['label' => 'Points d\'expérience', 'fieldName' => 'xp',  'sortFieldName' => 'xp', 'tooltip' => 'Points d\'expérience actuels sur le total max possible'],
        'colHasAnomalie' => ['label' => 'Ano.', 'fieldName' => 'hasAnomalie',  'sortFieldName' => 'hasAnomalie', 'tooltip' => 'Une pastille orange indique une anomalie'],
    ];

    /**
     * Retourne le tableau de paramètres à utiliser pour l'affichage de la recherche des personnages.
     */
    public function getSearchViewParameters(
        Request $request,
        EntityManagerInterface $entityManager,
        string $routeName): array
    {
        // récupère les filtres et tris de recherche + pagination renseignés dans le formulaire
        $orderBy = $request->get('order_by') ?: 'id';
        $orderDir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $isAsc = 'ASC' == $orderDir;
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $criteria = [];

        $formData = $request->query->all('personnage_find_form');
        $religion = isset($formData['religion']) ? $entityManager->find('App\Entity\Religion', $formData['religion']) : null;
        $competence = isset($formData['competence']) ? $entityManager->find('App\Entity\Competence', $formData['competence']) : null;
        $classe = isset($formData['classe']) ? $entityManager->find('App\Entity\Classe', $formData['classe']) : null;
        $groupe = isset($formData['groupe']) ? $entityManager->find('App\Entity\Groupe', $formData['groupe']) : null;
        $optionalParameters = '';

        // construit le formulaire contenant les filtres de recherche
        $form = $this->createForm(
            PersonnageFindForm::class,
            null,
            [
                'data' => [
                    'religion' => $religion,
                    'classe' => $classe,
                    'competence' => $competence,
                    'groupe' => $groupe,
                ],
                'method' => 'get',
                'csrf_protection' => false,
            ]
        );

        $form->handleRequest($request);

        // récupère les nouveaux filtres de recherche
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['value'];
            $criteria[$type] = $value;
        }
        if ($religion) {
            $criteria['religion'] = $religion->getId();
            $optionalParameters .= "&personnageFind[religion]={$religion->getId()}";
        }
        if ($competence) {
            $criteria['competence'] = $competence->getId();
            $optionalParameters .= "&personnageFind[competence]={$competence->getId()}";
        }
        if ($classe) {
            $criteria['classe'] = $classe->getId();
            $optionalParameters .= "&personnageFind[classe]={$classe->getId()}";
        }
        if ($groupe) {
            $criteria['groupe'] = $groupe->getId();
            $optionalParameters .= "&personnageFind[groupe]={$groupe->getId()}";
        }

        $repo = $entityManager->getRepository(Personnage::class);

        // attention, il y a des propriétés sur lesquelles on ne peut pas appliquer le order by
        // car elles ne sont pas en base mais calculées, ça compliquerait trop le sql
        $orderByCalculatedFields = new ArrayCollection(['pugilat', 'heroisme', 'user', 'hasAnomalie', 'status']);
        if ($orderByCalculatedFields->contains($orderBy)) {
            // recherche basée uniquement sur les filtres
            $filteredPersonnages = $repo->findList($criteria)->getResult();
            // on applique le tri
            PersonnageManager::sort($filteredPersonnages, $orderBy, $isAsc);
            $personnagesCollection = new ArrayCollection($filteredPersonnages);
            // on découpe suivant la pagination demandée
            $personnages = $personnagesCollection->slice($offset, $limit);
        } else {
            // recherche et applique directement en sql filtres + tri + pagination
            $personnages = $repo->findList(
                $criteria,
                ['by' => $orderBy, 'dir' => $orderDir],
                $limit,
                $offset
            );
        }

        $paginator = $repo->findPaginatedQuery(
            $personnages,
            $this->getRequestLimit(),
            $this->getRequestPage()
        );

        // récupère les colonnes à afficher
        if (empty($columnKeys)) {
            // on prend l'ordre par défaut
            $columnDefinitions = $this->columnDefinitions;
        } else {
            // on reconstruit le tableau dans l'ordre demandé
            $columnDefinitions = [];
            foreach ($columnKeys as $columnKey) {
                if (array_key_exists($columnKey, $this->columnDefinitions)) {
                    $columnDefinitions[] = $this->columnDefinitions[$columnKey];
                }
            }
        }

        return array_merge([
            'personnages' => $personnages,
            'paginator' => $paginator,
            'form' => $form->createView(),
            'optionalParameters' => $optionalParameters,
            'columnDefinitions' => $columnDefinitions,
            'formPath' => $routeName,
        ]
        );
    }

    /**
     * Selection du personnage courant.
     */
    public function selectAction(Request $request, EntityManagerInterface $entityManager, Personnage $personnage)
    {
        $app['personnage.manager']->setCurrentPersonnage($personnage->getId());

        return $this->redirectToRoute('homepage', [], 303);
    }

    /**
     * Dé-Selection du personnage courant.
     */
    public function unselectAction(Request $request, EntityManagerInterface $entityManager)
    {
        $app['personnage.manager']->resetCurrentPersonnage();

        return $this->redirectToRoute('homepage', [], 303);
    }

    /**
     * Obtenir une image protégée.
     */
    #[Route('/{personnage}/trombine', name: 'trombine')]
    public function getTrombineAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage): Response
    {
        $trombine = $personnage->getTrombineUrl();
        $filename = $this->fileUploader->getDirectory(FolderType::Photos).$trombine;

        // Larpv1 temp
        if (!is_file($filename)) {
            $filename = $this->fileUploader->getDirectory(FolderType::Asset).'../../larpmanager/private/img/'.$trombine;
        }

        if (!is_file($filename)) {
            return $this->sendNoImageAvailable();
        }

        $response = new Response(file_get_contents($filename));
        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('cache-control', 'private');

        return $response;
    }

    /**
     * Mise à jour de la photo.
     */
    #[Route('/{personnage}/updateTrombine', name: 'trombine.update')]
    public function updateTrombineAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $form = $this->createForm(TrombineForm::class, [])
            ->add('envoyer', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Envoyer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $files = $request->files->get($form->getName());

            $path = __DIR__.'/../../assets/img/';
            $filename = $files['trombine']->getClientOriginalName();
            $extension = $files['trombine']->guessExtension();

            if (!$extension || !in_array($extension, ['png', 'jpg', 'jpeg', 'bmp'])) {
                $this->addFlash('error', 'Désolé, votre image ne semble pas valide (vérifiez le format de votre image)');

                return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
            }

            $trombineFilename = hash('md5', $this->getUser()->getUsername().$filename.time()).'.'.$extension;

            $image = $app['imagine']->open($files['trombine']->getPathname());
            $image->resize($image->getSize()->widen(160));
            $image->save($path.$trombineFilename);

            $personnage->setTrombineUrl($trombineFilename);
            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'La photo a été enregistrée');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/trombine.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Création d'un nouveau personnage.
     */
    #[Route('/{personnage}/add', name: 'add')]
    public function newAction(Request $request, EntityManagerInterface $entityManager)
    {
        $personnage = new Personnage();

        $form = $this->createForm(PersonnageForm::class, $personnage)
            ->add('valider', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();
            $this->getUser()->addPersonnage($personnage);
            $entityManager->persist($this->getUser());
            $entityManager->persist($personnage);
            $entityManager->flush();

            $app['personnage.manager']->setCurrentPersonnage($personnage);
            $this->addFlash('success', 'Votre personnage a été créé');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('personnage/new.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Page d'accueil de gestion des personnage.
     */
    public function accueilAction(Request $request, EntityManagerInterface $entityManager)
    {
        return $this->render('personnage/accueil.twig', []);
    }

    /**
     * Permet de faire vieillir les personnages
     * Cela va donner un Jeton Vieillesse à tous les personnages et changer la catégorie d'age des personnages cumulants deux jetons vieillesse.
     */
    // TODO
    #[Route('/vieillir', name: 'vieillir')]
    public function vieillirAction(Request $request, EntityManagerInterface $entityManager)
    {
        $form = $this->createForm()
            ->add('valider', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Faire vieillir tous les personnages', 'attr' => ['class' => 'btn-danger']]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnages = $entityManager->getRepository('\\'.Personnage::class)->findAll();
            $token = $entityManager->getRepository('\\'.\App\Entity\Token::class)->findOneByTag('VIEILLESSE');
            $ages = $entityManager->getRepository('\\'.\App\Entity\Age::class)->findAll();

            if (!$token) {
                $this->addFlash('error', "Le jeton VIEILLESSE n'existe pas !");

                return $this->redirectToRoute('homepage', [], 303);
            }

            foreach ($personnages as $personnage) {
                // donne un jeton vieillesse
                $personnageHasToken = new PersonnageHasToken();
                $personnageHasToken->setToken($token);
                $personnageHasToken->setPersonnage($personnage);
                $personnage->addPersonnageHasToken($personnageHasToken);
                $entityManager->persist($personnageHasToken);

                if (true == $personnage->getVivant()) {
                    $personnage->setAgeReel($personnage->getAgeReel() + 5); // ajoute 5 ans à l'age réél
                }

                if (0 == $personnage->getPersonnageHasTokens()->count() % 2 && true == $personnage->getVivant()) {
                    if ($personnage->getAge()->getId() < 5) {
                        $personnage->setAge($ages[$personnage->getAge()->getId()]);
                    } elseif (5 == $personnage->getAge()->getId()) {
                        $personnage->setVivant(false);
                        foreach ($personnage->getParticipants() as $participant) {
                            if (null != $participant->getGn()) {
                                $anneeGN = $participant->getGn()->getDateJeu() + rand(1, 4);
                            }
                        }

                        $evenement = 'Mort de vieillesse';
                        $personnageChronologie = new PersonnageChronologie();
                        $personnageChronologie->setAnnee($anneeGN);
                        $personnageChronologie->setEvenement($evenement);
                        $personnageChronologie->setPersonnage($personnage);
                        $entityManager->persist($personnageChronologie);
                    }
                }

                $entityManager->persist($personnage);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Tous les personnages ont reçu un jeton vieillesse.');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('personnage/vieillir.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifier l'age d'un personnage.
     */
    #[Route('/{personnage}/updateAge', name: 'admin.update.age')]
    public function adminUpdateAgeAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $form = $this->createForm(PersonnageUpdateAgeForm::class, $personnage)
            ->add('valider', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();

            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/age.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modification des technologies d'un personnage.
     */
    #[Route('/{personnage}/updateTechnologie', name: 'admin.update.technologie')]
    public function adminUpdateTechnologieAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $technologies = $entityManager->getRepository(Technologie::class)->findAllOrderedByLabel();
        $competences = $personnage->getCompetences();

        /*
                $competenceFamilies = array();
                $competencesExpert = array();

                foreach ($competences as $competence){
                        $competenceFamilies[] = $competence->getCompetenceFamily()->getId();
                }
                $competencesNiveaux = array_count_values($competenceFamilies);
                foreach ($competencesNiveaux as $competence => $count){
                        if ($count > 2) {
                                $competencesExpert [] = $competence;
                        }
                }
        */
        $message = $personnage->getNom()." n'est pas au moins Initié en Artisanat.";
        $limit = 1;
        foreach ($competences as $competence) {
            if ('Artisanat' == $competence->getCompetenceFamily()->getLabel()) {
                if ($competence->getLevel()->getIndex() >= 2) {
                    $message = false;
                }

                if (3 == $competence->getLevel()->getIndex()) {
                    ++$limit;
                }

                if ($competence->getLevel()->getIndex() >= 4) {
                    $limit += 1000;
                }
            }
        }

        if (count($personnage->getTechnologies()) >= $limit) {
            $message = $personnage->getNom().' connait déjà au moins '.$limit.' Technologie(s).';
        }

        $form = $this->createForm(PersonnageTechnologieForm::class, $personnage)
            ->add('valider', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();

            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/updateTechnologie.twig', [
            'personnage' => $personnage,
            'technologies' => $technologies,
            'message' => $message,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute une technologie à un personnage.
     *
     * @return RedirectResponse
     */
    public function adminAddTechnologieAction(Request $request, EntityManagerInterface $entityManager)
    {
        $technologieId = $request->get('technologie');
        $personnage = $request->get('personnage');

        $technologie = $entityManager->getRepository(Technologie::class)
            ->find($technologieId);

        $nomTechnologie = $technologie->getLabel();

        $personnage->addTechnologie($technologie);

        $entityManager->flush();

        $this->addFlash('success', $nomTechnologie.' a été ajoutée.');

        return $this->redirectToRoute('personnage.technologie.update', ['personnage' => $personnage->getId(), 303]);
    }

    /**
     * Retire une technologie à un personnage.
     *
     * @return RedirectResponse
     */
    public function adminRemoveTechnologieAction(Request $request, EntityManagerInterface $entityManager)
    {
        $technologieId = $request->get('technologie');
        $personnage = $request->get('personnage');

        $technologie = $entityManager->getRepository(Technologie::class)
            ->find($technologieId);

        $nomTechnologie = $technologie->getLabel();

        $personnage->removeTechnologie($technologie);

        $entityManager->flush();

        $this->addFlash('success', $nomTechnologie.' a été retirée.');

        return $this->redirectToRoute('personnage.technologie.update', ['personnage' => $personnage->getId(), 303]);
    }

    /**
     * @return non-falsy-string[]
     */
    public function getLangueMateriel(Personnage $personnage): array
    {
        $langueMateriel = [];
        foreach ($personnage->getPersonnageLangues() as $langue) {
            if ($langue->getLangue()->getGroupeLangue()->getId() > 0 && $langue->getLangue()->getGroupeLangue()->getId() < 6) {
                if (!in_array('Bracelet '.$langue->getLangue()->getGroupeLangue()->getCouleur(), $langueMateriel)) {
                    $langueMateriel[] = 'Bracelet '.$langue->getLangue()->getGroupeLangue()->getCouleur();
                }
            }

            if (0 === $langue->getLangue()->getDiffusion()) {
                $langueMateriel[] = 'Alphabet '.$langue->getLangue()->getLabel();
            }
        }
        sort($langueMateriel);

        return $langueMateriel;
    }

    #[Route('/{personnage}/enveloppe/print', name: 'enveloppe.print')]
    public function enveloppePrintAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        return $this->render('personnage/enveloppe.twig', [
            'personnage' => $personnage,
            'langueMateriel' => $this->getLangueMateriel($personnage),
        ]);
    }

    /**
     * Modifie le matériel lié à un personnage.
     */
    #[Route('/{personnage}/updateMateriel', name: 'update.materiel')]
    public function adminMaterielAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $form = $this->createForm()
            ->add('materiel', 'textarea', [
                'required' => false,
                'data' => $personnage->getMateriel(),
            ])
            ->add('valider', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $personnage->setMateriel($data['materiel']);
            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/materiel.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modification du statut d'un personnage.
     */
    #[Route('/{personnage}/statut', name: 'admin.statut')]
    public function adminStatutAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $form = $this->createForm(PersonnageStatutForm::class, $personnage)
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();
            $evenement = false == $personnage->getVivant() ? 'Mort violente' : 'Résurrection';

            // TODO: Trouver comment avoir la date du GN
            /*
            $personnageChronologie = new \App\Entity\PersonnageChronologie();
            $personnageChronologie->setAnnee($anneeGN);
            $personnageChronologie->setEvenement($evenement);
            $personnageChronologie->setPersonnage($personnage);
            $entityManager->persist($personnageChronologie);
            */

            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le statut du personnage a été modifié');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/statut.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Transfert d'un personnage à un autre utilisateur.
     */
    #[Route('/{personnage}/transfert', name: 'admin.transfert')]
    public function adminTransfertAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $form = $this->createForm()
            ->add('participant', 'entity', [
                'required' => true,
                'expanded' => true,
                'label' => 'Nouveau propriétaire',
                'class' => \App\Entity\Participant::class,
                'choice_label' => 'UserIdentity',
            ])
            ->add('transfert', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Transferer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $newParticipant = $data['participant'];
            $oldParticipant = $personnage->getLastParticipant();

            $personnage->setUser($newParticipant->getUser());

            // gestion de l'ancien personnage
            if ($newParticipant->getPersonnage()) {
                $oldPersonnage = $newParticipant->getPersonnage();
                $oldPersonnage->removeParticipant($newParticipant);
                $oldPersonnage->setGroupeNull();
            }

            // le personnage doit rejoindre le groupe de l'utilisateur
            if ($newParticipant->getGroupeGn() && $newParticipant->getGroupeGn()->getGroupe()) {
                $personnage->setGroupe($newParticipant->getGroupeGn()->getGroupe());
            }

            $oldParticipant->setPersonnageNull();
            $oldParticipant->getUser()->setPersonnage(null);
            $newParticipant->setPersonnage($personnage);
            $newParticipant->getUser()->setPersonnage($personnage);
            $personnage->addParticipant($newParticipant);

            $entityManager->persist($oldParticipant);
            $entityManager->persist($newParticipant);
            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été transféré');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/transfert.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @return mixed[]
     */
    private function getErrorMessages(\Symfony\Component\Form\Form $form): array
    {
        $errors = [];

        foreach ($form->getErrors() as $key => $error) {
            if ($form->isRoot()) {
                $errors['#'][] = $error->getMessage();
            } else {
                $errors[] = $error->getMessage();
            }
        }

        foreach ($form->all() as $child) {
            if (!$child->isValid()) {
                $errors[$child->getName()] = $this->getErrorMessages($child);
            }
        }

        return $errors;
    }

    /**
     * Liste des personnages.
     */
    #[Route('/admin/list', name: 'admin.list')]
    #[Route('/list', name: 'list')]
    public function adminListAction(Request $request, EntityManagerInterface $entityManager)
    {
        $routeName = 'personnage.admin.list';
        $twigFilePath = 'admin/personnage/list.twig';

        // handle the request and return an array containing the parameters for the view
        $viewParams = $this->getSearchViewParameters($request, $entityManager, $routeName);

        return $this->render($twigFilePath, $viewParams);
    }

    /**
     * Imprimer la liste des personnages.
     */
    public function adminPrintAction(Request $request, EntityManagerInterface $entityManager): void
    {
        // TODO
    }

    /**
     * Télécharger la liste des personnages au format CSV.
     */
    public function adminDownloadAction(Request $request, EntityManagerInterface $entityManager): void
    {
        // TODO
    }

    /**
     * Affiche le détail d'un personnage (pour les orgas).
     */
    #[Route('/{personnage}/admin', name: 'admin.detail')]
    #[Route('/{personnage}', name: 'detail')]
    #[Route('/admin/{personnage}/detail', name: 'admin.detail')] // larp V1 url
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this.')]
    public function adminDetailAction(EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage): Response
    {
        $descendants = $entityManager->getRepository(Personnage::class)->findDescendants($personnage);

        return $this->render(
            'personnage/detail.twig',
            [
                'personnage' => $personnage,
                'descendants' => $descendants,
                'langueMateriel' => $this->getLangueMateriel($personnage),
                'participant' => $personnage->getLastParticipant()
            ]
        );
    }

    /**
     * Gestion des points d'expérience d'un personnage (pour les orgas).
     */
    #[Route('/{personnage}/xp', name: 'admin.xp')]
    public function adminXpAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $form = $this->createForm(PersonnageXpForm::class, [])
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $xp = $personnage->getXp();

            $personnage->setXp($xp + $data['xp']);

            // historique
            $historique = new ExperienceGain();
            $historique->setOperationDate(new \DateTime('NOW'));
            $historique->setXpGain($data['xp']);
            $historique->setExplanation($data['explanation']);
            $historique->setPersonnage($personnage);

            $entityManager->persist($personnage);
            $entityManager->persist($historique);
            $entityManager->flush();

            $this->addFlash('success', 'Les points d\'expériences ont été ajoutés');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/xp.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajout d'un personnage (orga seulement).
     */
    #[Route('/add', name: 'admin.add')]
    public function adminAddAction(Request $request, EntityManagerInterface $entityManager)
    {
        $personnage = new Personnage();
        $gnActif = GroupeManager::getGnActif($entityManager);

        $participant = $request->get('participant');
        if (!$participant) {
            // essaye de récupérer le participant du gn actif

            if ($gnActif) {
                $participant = $this->getUser()->getParticipant($gnActif);
            }

            if (!$participant) {
                // sinon récupère le dernier dans la liste
                $participant = $this->getUser()->getLastParticipant();
            }
        } else {
            $participant = $entityManager->getRepository('\\'.\App\Entity\Participant::class)->find($participant);
        }

        $form = $this->createForm(PersonnageForm::class, $personnage)
            ->add('classe', 'entity', [
                'label' => 'Classes disponibles',
                'choice_label' => 'label',
                'class' => \App\Entity\Classe::class,
            ])
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();
            if ($participant) {
                $participant->setPersonnage($personnage);

                if ($participant->getGroupe()) {
                    $personnage->setGroupe($participant->getGroupe());
                }
            }

            $personnage->setXp($gnActif->getXpCreation());

            // historique
            $historique = new ExperienceGain();
            $historique->setExplanation('Création de votre personnage');
            $historique->setOperationDate(new \DateTime('NOW'));
            $historique->setPersonnage($personnage);
            $historique->setXpGain($gnActif->getXpCreation());
            $entityManager->persist($historique);

            // ajout des compétences acquises à la création
            foreach ($personnage->getClasse()->getCompetenceFamilyCreations() as $competenceFamily) {
                $firstCompetence = $competenceFamily->getFirstCompetence();
                if ($firstCompetence) {
                    $personnage->addCompetence($firstCompetence);
                    $firstCompetence->addPersonnage($personnage);
                    $entityManager->persist($firstCompetence);
                }
            }

            // Ajout des points d'expérience gagné grace à l'age
            $xpAgeBonus = $personnage->getAge()->getBonus();
            if ($xpAgeBonus) {
                $personnage->addXp($xpAgeBonus);
                $historique = new ExperienceGain();
                $historique->setExplanation("Bonus lié à l'age");
                $historique->setOperationDate(new \DateTime('NOW'));
                $historique->setPersonnage($personnage);
                $historique->setXpGain($xpAgeBonus);
                $entityManager->persist($historique);
            }

            $entityManager->persist($personnage);
            if ($participant) {
                $entityManager->persist($participant);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Votre personnage a été sauvegardé.');
            if ($participant && $participant->getGroupe()) {
                return $this->redirectToRoute('groupe.detail', ['groupe' => $participant->getGroupe()->getId()], 303);
            } else {
                return $this->redirectToRoute('homepage', [], 303);
            }
        }

        return $this->render('personnage/add.twig', [
            'form' => $form->createView(),
            'participant' => $participant,
        ]);
    }

    /**
     * Supression d'un personnage (orga seulement).
     */
    #[Route('/{personnage}/delete', name: 'admin.delete')]
    public function adminDeleteAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $form = $this->createForm(PersonnageDeleteForm::class, $personnage)
            ->add('delete', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();

            foreach ($personnage->getExperienceGains() as $xp) {
                $personnage->removeExperienceGain($xp);
                $entityManager->remove($xp);
            }

            foreach ($personnage->getExperienceUsages() as $xp) {
                $personnage->removeExperienceUsage($xp);
                $entityManager->remove($xp);
            }

            foreach ($personnage->getMembres() as $membre) {
                $personnage->removeMembre($membre);
                $entityManager->remove($membre);
            }

            foreach ($personnage->getPersonnagesReligions() as $personnagesReligions) {
                $personnage->removePersonnagesReligions($personnagesReligions);
                $entityManager->remove($personnagesReligions);
            }

            foreach ($personnage->getPostulants() as $postulant) {
                $personnage->removePostulant($postulant);
                $entityManager->remove($postulant);
            }

            foreach ($personnage->getPersonnageLangues() as $personnageLangue) {
                $personnage->removePersonnageLangues($personnageLangue);
                $entityManager->remove($personnageLangue);
            }

            foreach ($personnage->getPersonnageTriggers() as $trigger) {
                $personnage->removePersonnageTrigger($trigger);
                $entityManager->remove($trigger);
            }

            foreach ($personnage->getPersonnageBackgrounds() as $background) {
                $personnage->removePersonnageBackground($background);
                $entityManager->remove($background);
            }

            foreach ($personnage->getPersonnageHasTokens() as $token) {
                $personnage->removePersonnageHasToken($token);
                $entityManager->remove($token);
            }

            foreach ($personnage->getParticipants() as $participant) {
                $participant->setPersonnage();
                $entityManager->persist($participant);
            }

            $entityManager->remove($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été supprimé.');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('personnage/delete.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Modification du personnage.
     */
    #[Route('/{personnage}/update', name: 'admin.update')]
    public function adminUpdateAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $form = $this->createForm(PersonnageUpdateForm::class, $personnage)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider les modifications'])
        ;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();

            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/update.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage]);
    }

    /**
     * Ajoute un background au personnage.
     */
    #[Route('/{personnage}/addBackground', name: 'admin.add.background')]
    public function adminAddBackgroundAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $background = new \App\Entity\PersonnageBackground();

        $background->setPersonnage($personnage);
        $background->setUser($this->getUser());

        $form = $this->createForm(PersonnageBackgroundForm::class, $background)
            ->add('visibility', 'choice', [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => $app['larp.manager']->getPersonnageBackgroundVisibility(),
            ])
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider les modifications']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $background = $form->getData();

            $entityManager->persist($background);
            $entityManager->flush();

            $this->addFlash('success', 'Le background a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/addBackground.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'background' => $background,
        ]);
    }

    /**
     * Modifie le background d'un personnage.
     */
    #[Route('/{personnage}/updateBackground', name: 'admin.update.background')]
    public function adminUpdateBackgroundAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $personnage = $request->get('personnage');
        $background = $request->get('background');

        $form = $this->createForm(PersonnageBackgroundForm::class, $background)
            ->add('visibility', 'choice', [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => $app['larp.manager']->getPersonnageBackgroundVisibility(),
            ])
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider les modifications']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $background = $form->getData();

            $entityManager->persist($background);
            $entityManager->flush();

            $this->addFlash('success', 'Le background a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/updateBackground.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'background' => $background,
        ]);
    }

    /**
     * Modification de la renommee du personnage.
     */
    #[Route('/{personnage}/updateRenomme', name: 'admin.update.renomme')]
    public function adminUpdateRenommeAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $form = $this->createForm(PersonnageUpdateRenommeForm::class)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider les modifications']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $renomme = $form->get('renomme')->getData();
            $explication = $form->get('explication')->getData();

            $renomme_history = new RenommeHistory();

            $renomme_history->setRenomme($renomme);
            $renomme_history->setExplication($explication);
            $renomme_history->setPersonnage($personnage);
            $personnage->addRenomme($renomme);

            $entityManager->persist($renomme_history);
            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/updateRenomme.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage]);
    }

    /**
     * Modification de l'héroisme d'un personnage.
     */
    #[Route('/{personnage}/updateHeroisme', name: 'admin.update.heroisme')]
    public function adminUpdateHeroismeAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $form = $this->createForm(PersonnageUpdateHeroismeForm::class)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider les modifications']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $heroisme = $form->get('heroisme')->getData();
            $explication = $form->get('explication')->getData();

            $heroisme_history = new \App\Entity\HeroismeHistory();

            $heroisme_history->setHeroisme($heroisme);
            $heroisme_history->setExplication($explication);
            $heroisme_history->setPersonnage($personnage);
            $personnage->addHeroisme($heroisme);

            $entityManager->persist($heroisme_history);
            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/updateHeroisme.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage]);
    }

    /**
     * Modification du pugilat d'un personnage.
     */
    #[Route('/{personnage}/updatePugilat', name: 'admin.update.pugilat')]
    public function adminUpdatePugilatAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $form = $this->createForm(PersonnageUpdatePugilatForm::class)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider les modifications']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pugilat = $form->get('pugilat')->getData();
            $explication = $form->get('explication')->getData();

            $pugilat_history = new \App\Entity\PugilatHistory();

            $pugilat_history->setPugilat($pugilat);
            $pugilat_history->setExplication($explication);
            $pugilat_history->setPersonnage($personnage);
            $personnage->addPugilat($pugilat);

            $entityManager->persist($pugilat_history);
            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/updatePugilat.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage]);
    }

    /**
     * Ajoute un jeton vieillesse au personnage.
     */
    #[Route('/{personnage}/addToken', name: 'admin.token.add')]
    public function adminTokenAddAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $token = $request->get('token');
        $token = $entityManager->getRepository('\\'.\App\Entity\Token::class)->findOneByTag($token);

        // donne un jeton vieillesse
        $personnageHasToken = new PersonnageHasToken();
        $personnageHasToken->setToken($token);
        $personnageHasToken->setPersonnage($personnage);

        $personnage->addPersonnageHasToken($personnageHasToken);
        $entityManager->persist($personnageHasToken);

        $personnage->setAgeReel($personnage->getAgeReel() + 5); // ajoute 5 ans à l'age réél

        if (0 == $personnage->getPersonnageHasTokens()->count() % 2) {
            if (5 != $personnage->getAge()->getId()) {
                $age = $entityManager->getRepository('\\'.\App\Entity\Age::class)->findOneById($personnage->getAge()->getId() + 1);
                $personnage->setAge($age);
            } else {
                $personnage->setVivant(false);
                foreach ($personnage->getParticipants() as $participant) {
                    if (null != $participant->getGn()) {
                        $anneeGN = $participant->getGn()->getDateJeu() + rand(1, 4);
                    }
                }

                $evenement = 'Mort de vieillesse';
                $personnageChronologie = new PersonnageChronologie();
                $personnageChronologie->setAnnee($anneeGN);
                $personnageChronologie->setEvenement($evenement);
                $personnageChronologie->setPersonnage($personnage);
                $entityManager->persist($personnageChronologie);
            }
        }

        $entityManager->persist($personnage);
        $entityManager->flush();
        $this->addFlash('success', 'Le jeton '.$token->getTag().' a été ajouté.');

        return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
    }

    /**
     * Retire un jeton d'un personnage.
     */
    #[Route('/{personnage}/deleteToken', name: 'admin.token.delete')]
    public function adminTokenDeleteAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage, PersonnageHasToken $personnageHasToken)
    {
        $personnage->removePersonnageHasToken($personnageHasToken);
        // $personnage->setAgeReel($personnage->getAgeReel() - 5);
        if (0 != $personnage->getPersonnageHasTokens()->count() % 2 && 5 != $personnage->getAge()->getId()) {
            $age = $entityManager->getRepository('\\'.\App\Entity\Age::class)->findOneById($personnage->getAge()->getId() - 1);
            $personnage->setAge($age);
        }

        $entityManager->remove($personnageHasToken);
        $entityManager->persist($personnage);

        // Chronologie : Fruits & Légumes
        foreach ($personnage->getParticipants() as $participant) {
            if (null != $participant->getGn()) {
                $anneeGN = $participant->getGn()->getDateJeu() + rand(-2, 2);
            }
        }

        $evenement = 'Consommation de Fruits & Légumes';
        $personnageChronologie = new PersonnageChronologie();
        $personnageChronologie->setAnnee($anneeGN);
        $personnageChronologie->setEvenement($evenement);
        $personnageChronologie->setPersonnage($personnage);
        $entityManager->persist($personnageChronologie);

        $entityManager->flush();

        $this->addFlash('success', 'Le jeton a été retiré.');

        return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
    }

    /**
     * Ajoute un trigger.
     */
    #[Route('/{personnage}/addTrigger', name: 'admin.trigger.add')]
    public function adminTriggerAddAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $trigger = new \App\Entity\PersonnageTrigger();
        $trigger->setPersonnage($personnage);
        $trigger->setDone(false);

        $form = $this->createForm(TriggerForm::class, $trigger)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider les modifications']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $trigger = $form->getData();

            $entityManager->persist($trigger);
            $entityManager->flush();

            $this->addFlash('success', 'Le déclencheur a été ajouté.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/addTrigger.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage]);
    }

    /**
     * Supprime un trigger.
     */
    #[Route('/{personnage}/deleteTrigger', name: 'admin.trigger.delete')]
    public function adminTriggerDeleteAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $trigger = $request->get('trigger');

        $form = $this->createForm(TriggerDeleteForm::class, $trigger)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider les modifications']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $trigger = $form->getData();

            $entityManager->remove($trigger);
            $entityManager->flush();

            $this->addFlash('success', 'Le déclencheur a été supprimé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/deleteTrigger.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'trigger' => $trigger,
        ]);
    }

    /**
     * Modifie la liste des domaines de magie.
     */
    #[Route('/{personnage}/updateDomaine', name: 'admin.update.domaine')]
    public function adminUpdateDomaineAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $originalDomaines = new ArrayCollection();
        foreach ($personnage->getDomaines() as $domaine) {
            $originalDomaines[] = $domaine;
        }

        $form = $this->createForm(PersonnageUpdateDomaineForm::class, $personnage)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider les modifications']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();

            foreach ($personnage->getDomaines() as $domaine) {
                if (!$originalDomaines->contains($domaine)) {
                    $domaine->addPersonnage($personnage);
                }
            }

            foreach ($originalDomaines as $domaine) {
                if (!$personnage->getDomaines()->contains($domaine)) {
                    $domaine->removePersonnage($personnage);
                }
            }

            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/updateDomaine.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Modifie la liste des langues.
     */
    #[Route('/{personnage}/updateLangue', name: 'admin.update.langue')]
    public function adminUpdateLangueAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $langues = $entityManager->getRepository(\App\Entity\Langue::class)->findBy([], ['secret' => 'ASC', 'diffusion' => 'DESC', 'label' => 'ASC']);

        $originalLanguages = [];
        foreach ($personnage->getLanguages() as $languages) {
            $originalLanguages[] = $languages;
        }

        $form = $this->createForm()
            ->add('langues', 'entity', [
                'required' => true,
                'label' => 'Choisissez les langues du personnage',
                'multiple' => true,
                'expanded' => true,
                'class' => \App\Entity\Langue::class,
                'choices' => $langues,
                'choice_label' => 'label',
                'data' => $originalLanguages,
            ])
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider vos modifications']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $langues = $data['langues'];

            // pour toutes les nouvelles langues
            foreach ($langues as $langue) {
                if (!$personnage->isKnownLanguage($langue)) {
                    $personnageLangue = new \App\Entity\PersonnageLangues();
                    $personnageLangue->setPersonnage($personnage);
                    $personnageLangue->setLangue($langue);
                    $personnageLangue->setSource('ADMIN');
                    $entityManager->persist($personnageLangue);
                }
            }

            if (0 == count($langues)) {
                foreach ($personnage->getLanguages() as $langue) {
                    $personnageLangue = $personnage->getPersonnageLangue($langue);
                    $entityManager->remove($personnageLangue);
                }
            } else {
                foreach ($personnage->getLanguages() as $langue) {
                    $found = false;
                    foreach ($langues as $l) {
                        if ($l === $langue) {
                            $found = true;
                        }
                    }

                    if (!$found) {
                        $personnageLangue = $personnage->getPersonnageLangue($langue);
                        $entityManager->remove($personnageLangue);
                    }
                }
            }

            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/updateLangue.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Affiche la liste des prières pour modifications.
     */
    #[Route('/{personnage}/updatePriere', name: 'admin.update.priere')]
    public function adminUpdatePriereAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $order_by = $request->get('order_by', 'label');
        $order_dir = 'DESC' === $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) $request->get('limit', 50);
        $page = (int) $request->get('page', 1);
        $offset = ($page - 1) * $limit;
        $type = null;
        $value = null;

        $form = $this->createForm(PriereFindForm::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['value'];
        }

        $repo = $entityManager->getRepository('\\'.Priere::class);
        $prieres = $repo->findList(
            $type,
            $value,
            ['by' => $order_by, 'dir' => $order_dir],
            $limit,
            $offset
        );

        $numResults = $repo->findCount($type, $value);

        $url = $app['url_generator']->generate('personnage.admin.update.priere', ['personnage' => $personnage->getId()]);
        $paginator = new Paginator(
            $numResults,
            $limit,
            $page,
            $url.'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir
        );

        return $this->render('personnage/updatePriere.twig',
            [
                'prieres' => $prieres,
                'personnage' => $personnage,
                'paginator' => $paginator,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Ajoute une priere à un personnage.
     *
     * @return RedirectResponse
     */
    #[Route('/{personnage}/addPriere', name: 'admin.add.priere')]
    public function adminAddPriereAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $priereID = $request->get('priere');

        $priere = $entityManager->getRepository(Priere::class)
            ->find($priereID);

        $nomPriere = $priere->getLabel();

        $priere->addPersonnage($personnage);

        $entityManager->flush();

        $this->addFlash('success', $nomPriere.' a été ajoutée.');

        return $this->redirectToRoute('personnage.admin.update.priere', ['personnage' => $personnage->getId(), 303]);
    }

    /**
     * Retire une priere à un personnage.
     *
     * @return RedirectResponse
     */
    public function adminRemovePriereAction(Request $request, EntityManagerInterface $entityManager)
    {
        $priereID = $request->get('priere');
        $personnage = $request->get('personnage');

        $priere = $entityManager->getRepository(Priere::class)
            ->find($priereID);

        $nomPriere = $priere->getLabel();

        $priere->removePersonnage($personnage);

        $entityManager->flush();

        $this->addFlash('success', $nomPriere.' a été retirée.');

        return $this->redirectToRoute('personnage.admin.update.priere', ['personnage' => $personnage->getId(), 303]);
    }

    /**
     * Affiche la liste des connaissances pour modification.
     */
    #[Route('/{personnage}/updateConnaissance', name: 'admin.update.connaissance')]
    public function adminUpdateConnaissanceAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $connaissances = $entityManager->getRepository(Connaissance::class)->findAllOrderedByLabel();

        return $this->render('personnage/updateConnaissance.twig', [
            'personnage' => $personnage,
            'connaissances' => $connaissances,
        ]);
    }

    /**
     * Ajoute une connaissance à un personnage.
     *
     * @return RedirectResponse
     */
    #[Route('/{personnage}/addConnaissance', name: 'admin.add.connaissance')]
    public function adminAddConnaissanceAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $connaissanceID = $request->get('connaissance');

        $connaissance = $entityManager->getRepository(Connaissance::class)
            ->find($connaissanceID);

        $nomConnaissance = $connaissance->getLabel();
        $niveauConnaissance = $connaissance->getNiveau();

        $personnage->addConnaissance($connaissance);

        $entityManager->flush();

        $this->addFlash('success', $nomConnaissance.' '.$niveauConnaissance.' a été ajouté.');

        return $this->redirectToRoute('personnage.admin.update.connaissance', ['personnage' => $personnage->getId(), 303]);
    }

    /**
     * Retire une connaissance à un personnage.
     *
     * @return RedirectResponse
     */
    #[Route('/{personnage}/deleteConnaissance', name: 'admin.delete.connaissance')]
    public function adminRemoveConnaissanceAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $connaissanceID = $request->get('connaissance');

        $connaissance = $entityManager->getRepository(Connaissance::class)
            ->find($connaissanceID);

        $nomConnaissance = $connaissance->getLabel();
        $niveauConnaissance = $connaissance->getNiveau();

        $personnage->removeConnaissance($connaissance);

        $entityManager->flush();

        $this->addFlash('success', $nomConnaissance.' '.$niveauConnaissance.' a été retiré.');

        return $this->redirectToRoute('personnage.admin.update.connaissance', ['personnage' => $personnage->getId(), 303]);
    }

    /**
     * Affiche la liste des sorts pour modification.
     */
    #[Route('/{personnage}/updateSort', name: 'admin.update.sort')]
    public function adminUpdateSortAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $order_by = $request->get('order_by', 'label');
        $order_dir = 'DESC' === $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) $request->get('limit', 50);
        $page = (int) $request->get('page', 1);
        $offset = ($page - 1) * $limit;
        $type = null;
        $value = null;

        $form = $this->createForm(SortFindForm::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['value'];
        }

        $repo = $entityManager->getRepository('\\'.Sort::class);
        $sorts = $repo->findList(
            $type,
            $value,
            ['by' => $order_by, 'dir' => $order_dir],
            $limit,
            $offset
        );

        $numResults = $repo->findCount($type, $value);

        $url = $app['url_generator']->generate('personnage.admin.update.sort', ['personnage' => $personnage->getId()]);
        $paginator = new Paginator(
            $numResults,
            $limit,
            $page,
            $url.'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir
        );

        return $this->render('personnage/updateSort.twig',
            [
                'sorts' => $sorts,
                'personnage' => $personnage,
                'paginator' => $paginator,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Ajoute un sort à un personnage.
     *
     * @return RedirectResponse
     */
    #[Route('/{personnage}/addSort', name: 'admin.add.sort')]
    public function adminAddSortAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $sortID = $request->get('sort');

        $sort = $entityManager->getRepository(Sort::class)
            ->find($sortID);

        $nomSort = $sort->getLabel();
        $niveauSort = $sort->getNiveau();

        $personnage->addSort($sort);

        $entityManager->flush();

        $this->addFlash('success', $nomSort.' '.$niveauSort.' a été ajouté.');

        return $this->redirectToRoute('personnage.admin.update.sort', ['personnage' => $personnage->getId(), 303]);
    }

    /**
     * Retire un sort à un personnage.
     *
     * @return RedirectResponse
     */
    #[Route('/{personnage}/deleteSort', name: 'admin.delete.sort')]
    public function adminRemoveSortAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $sortID = $request->get('sort');

        $sort = $entityManager->getRepository(Sort::class)
            ->find($sortID);

        $nomSort = $sort->getLabel();
        $niveauSort = $sort->getNiveau();

        $personnage->removeSort($sort);

        $entityManager->flush();

        $this->addFlash('success', $nomSort.' '.$niveauSort.' a été retiré.');

        return $this->redirectToRoute('personnage.admin.update.sort', ['personnage' => $personnage->getId(), 303]);
    }

    /**
     * Affiche la liste des potions pour modification.
     */
    #[Route('/{personnage}/updatePotion', name: 'admin.update.potion')]
    public function adminUpdatePotionAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $order_by = $request->get('order_by', 'label');
        $order_dir = 'DESC' === $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) $request->get('limit', 50);
        $page = (int) $request->get('page', 1);
        $offset = ($page - 1) * $limit;
        $type = null;
        $value = null;

        $form = $this->createForm(PotionFindForm::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['value'];
        }

        $repo = $entityManager->getRepository('\\'.Potion::class);
        $potions = $repo->findList(
            $type,
            $value,
            ['by' => $order_by, 'dir' => $order_dir],
            $limit,
            $offset
        );

        $numResults = $repo->findCount($type, $value);

        $url = $app['url_generator']->generate('personnage.admin.update.potion', ['personnage' => $personnage->getId()]);
        $paginator = new Paginator(
            $numResults,
            $limit,
            $page,
            $url.'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir
        );

        return $this->render('personnage/updatePotion.twig',
            [
                'potions' => $potions,
                'personnage' => $personnage,
                'paginator' => $paginator,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Ajoute une potion à un personnage.
     *
     * @return RedirectResponse
     */
    #[Route('/{personnage}/addPotion', name: 'admin.add.potion')]
    public function adminAddPotionAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $potionID = $request->get('potion');

        $potion = $entityManager->getRepository(Potion::class)
            ->find($potionID);

        $nomPotion = $potion->getLabel();

        $personnage->addPotion($potion);

        $entityManager->flush();

        $this->addFlash('success', $nomPotion.' a été ajoutée.');

        return $this->redirectToRoute('personnage.admin.update.potion', ['personnage' => $personnage->getId(), 303]);
    }

    /**
     * Retire une potion à un personnage.
     *
     * @return RedirectResponse
     */
    #[Route('/{personnage}/deletePotion', name: 'admin.delete.potion')]
    public function adminRemovePotionAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $potionID = $request->get('potion');

        $potion = $entityManager->getRepository(Potion::class)
            ->find($potionID);

        $nomPotion = $potion->getLabel();

        $personnage->removePotion($potion);

        $entityManager->flush();

        $this->addFlash('success', $nomPotion.' a été retirée.');

        return $this->redirectToRoute('personnage.admin.update.potion', ['personnage' => $personnage->getId(), 303]);
    }

    /**
     * Modifie la liste des ingrédients.
     */
    #[Route('/{personnage}/updateIngredient', name: 'admin.update.ingredient')]
    public function adminUpdateIngredientAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $originalPersonnageIngredients = new ArrayCollection();

        /*
         *  Crée un tableau contenant les objets personnageIngredient du groupe
         */
        foreach ($personnage->getPersonnageIngredients() as $personnageIngredient) {
            $originalPersonnageIngredients->add($personnageIngredient);
        }

        $form = $this->createForm(PersonnageIngredientForm::class, $personnage);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();

            /*
             * Pour tous les ingredients
             */
            foreach ($personnage->getPersonnageIngredients() as $personnageIngredient) {
                $personnageIngredient->setPersonnage($personnage);
            }

            /*
             *  supprime la relation entre personnageIngredient et le personnage
             */
            foreach ($originalPersonnageIngredients as $personnageIngredient) {
                if (false == $personnage->getPersonnageIngredients()->contains($personnageIngredient)) {
                    $entityManager->remove($personnageIngredient);
                }
            }

            $random = $form['random']->getData();

            /*
             *  Gestion des ingrédients alloués au hasard
             */
            if ($random && $random > 0) {
                $ingredients = $entityManager->getRepository(\App\Entity\Ingredient::class)->findAllOrderedByLabel();
                shuffle($ingredients);
                $needs = new ArrayCollection(array_slice($ingredients, 0, $random));

                foreach ($needs as $ingredient) {
                    $pi = new PersonnageIngredient();
                    $pi->setIngredient($ingredient);
                    $pi->setNombre(1);
                    $pi->setPersonnage($personnage);
                    $entityManager->persist($pi);
                }
            }

            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/ingredients.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Modifie la liste des ressources.
     */
    #[Route('/{personnage}/updateRessource', name: 'admin.update.ressource')]
    public function adminUpdateRessourceAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $originalPersonnageRessources = new ArrayCollection();

        /*
         *  Crée un tableau contenant les objets personnageIngredient du groupe
         */
        foreach ($personnage->getPersonnageRessources() as $personnageRessource) {
            $originalPersonnageRessources->add($personnageRessource);
        }

        $form = $this->createForm(PersonnageRessourceForm::class, $personnage);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();

            /*
             * Pour toutes les ressources
             */
            foreach ($personnage->getPersonnageRessources() as $personnageRessource) {
                $personnageRessource->setPersonnage($personnage);
            }

            /*
             *  supprime la relation entre personnageRessource et le personnage
             */
            foreach ($originalPersonnageRessources as $personnageRessource) {
                if (false == $personnage->getPersonnageRessources()->contains($personnageRessource)) {
                    $entityManager->remove($personnageRessource);
                }
            }

            $randomCommun = $form['randomCommun']->getData();

            /*
             *  Gestion des ressources communes alloués au hasard
             */
            if ($randomCommun && $randomCommun > 0) {
                $ressourceCommune = $entityManager->getRepository(\App\Entity\Ressource::class)->findCommun();
                shuffle($ressourceCommune);
                $needs = new ArrayCollection(array_slice($ressourceCommune, 0, $randomCommun));

                foreach ($needs as $ressource) {
                    $pr = new PersonnageRessource();
                    $pr->setRessource($ressource);
                    $pr->setNombre(1);
                    $pr->setPersonnage($personnage);
                    $entityManager->persist($pr);
                }
            }

            $randomRare = $form['randomRare']->getData();

            /*
             *  Gestion des ressources rares alloués au hasard
             */
            if ($randomRare && $randomRare > 0) {
                $ressourceRare = $entityManager->getRepository(\App\Entity\Ressource::class)->findRare();
                shuffle($ressourceRare);
                $needs = new ArrayCollection(array_slice($ressourceRare, 0, $randomRare));

                foreach ($needs as $ressource) {
                    $pr = new PersonnageRessource();
                    $pr->setRessource($ressource);
                    $pr->setNombre(1);
                    $pr->setPersonnage($personnage);
                    $entityManager->persist($pr);
                }
            }

            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/ressources.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Modifie la richesse.
     */
    #[Route('/{personnage}/updateRichesse', name: 'admin.update.richesse')]
    public function adminUpdateRichesseAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $form = $this->createForm(PersonnageRichesseForm::class, $personnage);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();

            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/richesse.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Gestion des documents lié à un personnage.
     */
    #[Route('/{personnage}/documents', name: 'documents')]
    public function documentAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $form = $this->createForm(PersonnageDocumentForm::class, $personnage)
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();
            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le document a été ajouté au personnage.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/documents.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Gestion des objets lié à un personnage.
     */
    #[Route('/{personnage}/items', name: 'items')]
    public function itemAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $form = $this->createForm(PersonnageItemForm::class, $personnage)
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();
            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'L\'objet a été ajouté au personnage.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/items.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute une religion à un personnage.
     */
    #[Route('/{personnage}/addReligion', name: 'admin.add.religion')]
    public function adminAddReligionAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        // refUser la demande si le personnage est Fanatique
        if ($personnage->isFanatique()) {
            $this->addFlash('error', 'Désolé, le personnage êtes un Fanatique, il vous est impossible de choisir une nouvelle religion. (supprimer la religion fanatique qu\'il possède avant)');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        $personnageReligion = new \App\Entity\PersonnagesReligions();
        $personnageReligion->setPersonnage($personnage);

        // ne proposer que les religions que le personnage ne pratique pas déjà ...
        $availableReligions = $app['personnage.manager']->getAdminAvailableReligions($personnage);

        if (0 == $availableReligions->count()) {
            $this->addFlash('error', 'Désolé, il n\'y a plus de religion disponibles');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
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
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider votre religion']);

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

                    return $this->redirectToRoute('homepage', [], 303);
                }
            }

            $entityManager->persist($personnageReligion);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/addReligion.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage]);
    }

    /**
     * Retire une religion d'un personnage.
     */
    #[Route('/{personnage}/deleteReligion', name: 'admin.delete.religion')]
    public function adminRemoveReligionAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $personnageReligion = $request->get('personnageReligion');

        $form = $this->createForm()
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Retirer la religion']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $entityManager->remove($personnageReligion);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/removeReligion.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'personnageReligion' => $personnageReligion,
        ]);
    }

    /**
     * Retire une langue d'un personnage.
     */
    #[Route('/{personnage}/deleteLangue', name: 'admin.delete.langue')]
    public function adminRemoveLangueAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $personnageLangue = $request->get('personnageLangue');

        $form = $this->createForm()
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Retirer la langue']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $entityManager->remove($personnageLangue);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/removeLangue.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'personnageLangue' => $personnageLangue,
        ]);
    }

    /**
     * Modifie l'origine d'un personnage.
     */
    #[Route('/{personnage}/updateOrigine', name: 'admin.update.origine')]
    public function adminUpdateOriginAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $form = $this->createForm(PersonnageOriginForm::class, $personnage)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => "Valider l'origine du personnage"]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();

            // le personnage doit perdre les langues de son ancienne origine
            // et récupérer les langue de sa nouvelle origine
            foreach ($personnage->getPersonnageLangues() as $personnageLangue) {
                if ('ORIGINE' == $personnageLangue->getSource() || 'ORIGINE SECONDAIRE' == $personnageLangue->getSource()) {
                    $personnage->removePersonnageLangues($personnageLangue);
                    $entityManager->remove($personnageLangue);
                }
            }

            $newOrigine = $personnage->getTerritoire();
            foreach ($newOrigine->getLangues() as $langue) {
                $personnageLangue = new \App\Entity\PersonnageLangues();
                $personnageLangue->setPersonnage($personnage);
                $personnageLangue->setSource('ORIGINE SECONDAIRE');
                $personnageLangue->setLangue($langue);

                $entityManager->persist($personnageLangue);
                $personnage->addPersonnageLangues($personnageLangue);
            }

            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/updateOrigine.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Retire la dernière compétence acquise par un personnage.
     */
    #[Route('/{personnage}/deleteCompetence', name: 'admin.delete.competence')]
    public function removeCompetenceAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $lastCompetence = $app['personnage.manager']->getLastCompetence($personnage);

        if (!$lastCompetence) {
            $this->addFlash('error', 'Désolé, le personnage n\'a pas encore acquis de compétences');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        $form = $this->createForm()
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Retirer la compétence']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cout = $app['personnage.manager']->getCompetenceCout($personnage, $lastCompetence);
            $xp = $personnage->getXp();

            $personnage->setXp($xp + $cout);
            $personnage->removeCompetence($lastCompetence);
            $lastCompetence->removePersonnage($personnage);

            // historique xp
            $historiqueXP = new ExperienceGain();
            $historiqueXP->setOperationDate(new \DateTime('NOW'));
            $historiqueXP->setXpGain($cout);
            $historiqueXP->setExplanation('Suppression de la compétence '.$lastCompetence->getLabel());
            $historiqueXP->setPersonnage($personnage);

            // historique renommée
            $competenceNom = $lastCompetence->getCompetenceFamily()->getLabel();
            $competenceNiveau = $lastCompetence->getLevel()->getLabel();
            $competenceId = $lastCompetence->getid();

            // si compétence est Noblesse ou Stratégie(5)
            if (151 === $competenceId || 26 === $lastCompetence->getCompetenceFamily()->getId()) {
                if (97 === $competenceId) {
                    $renomme = -2;
                }

                if (98 === $competenceId) {
                    $renomme = -3;
                }

                if (99 === $competenceId) {
                    $renomme = -2;
                }

                if (100 === $competenceId) {
                    $renomme = -5;
                }

                if (101 === $competenceId) {
                    $renomme = -6;
                }

                if (151 === $competenceId) {
                    $renomme = -5;
                }

                $renommeHistory = new RenommeHistory();
                $renommeHistory->setDate(new \DateTime('NOW'));
                $renommeHistory->setPersonnage($personnage);
                $renommeHistory->setExplication('[Retrait] '.$competenceNom.' '.$competenceNiveau);
                $renommeHistory->setRenomme($renomme);

                $entityManager->persist($renommeHistory);
            }

            $entityManager->persist($lastCompetence);
            $entityManager->persist($personnage);
            $entityManager->persist($historiqueXP);
            $entityManager->flush();

            $this->addFlash('success', 'La compétence a été retirée');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/removeCompetence.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'competence' => $lastCompetence,
        ]);
    }

    #[Route('/{personnage}/addCompetence', name: 'admin.add.competence')]
    public function adminAddCompetenceAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $availableCompetences = $app['personnage.manager']->getAvailableCompetences($personnage);

        if (0 === $availableCompetences->count()) {
            $this->addFlash('error', 'Désolé, il n\'y a plus de compétence disponible.');

            return $this->redirectToRoute('homepage', [], 303);
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
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider la compétence']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $competenceId = $data['competenceId'];
            $competence = $entityManager->find(Competence::class, $competenceId);

            $cout = $app['personnage.manager']->getCompetenceCout($personnage, $competence);
            $xp = $personnage->getXp();

            if ($xp - $cout < 0) {
                $this->addFlash('error', 'Vos n\'avez pas suffisement de points d\'expérience pour acquérir cette compétence.');

                return $this->redirectToRoute('homepage', [], 303);
            }

            $personnage->setXp($xp - $cout);
            $personnage->addCompetence($competence);
            $competence->addPersonnage($personnage);

            // historique xp
            $historiqueXP = new ExperienceUsage();
            $historiqueXP->setOperationDate(new \DateTime('NOW'));
            $historiqueXP->setXpUse($cout);
            $historiqueXP->setCompetence($competence);
            $historiqueXP->setPersonnage($personnage);

            // historique renommée
            $competenceNom = $competence->getCompetenceFamily()->getLabel();
            $competenceNiveau = $competence->getLevel()->getLabel();

            // si la nouvelle compétence est Noblesse ou Stratégie(5)
            if (151 === $competenceId || 26 === $competence->getCompetenceFamily()->getId()) {
                if (97 === $competenceId) {
                    $renomme = 2;
                }

                if (98 === $competenceId) {
                    $renomme = 3;
                }

                if (99 === $competenceId) {
                    $renomme = 2;
                }

                if (100 === $competenceId) {
                    $renomme = 5;
                }

                if (101 === $competenceId) {
                    $renomme = 6;
                }

                if (151 === $competenceId) {
                    $renomme = 5;
                }

                $renommeHistory = new RenommeHistory();
                $renommeHistory->setDate(new \DateTime('NOW'));
                $renommeHistory->setPersonnage($personnage);
                $renommeHistory->setExplication('[Acquisition] '.$competenceNom.' '.$competenceNiveau);
                $renommeHistory->setRenomme($renomme);

                $entityManager->persist($renommeHistory);
            }

            $entityManager->persist($competence);
            $entityManager->persist($personnage);
            $entityManager->persist($historiqueXP);
            $entityManager->flush();

            $this->addFlash('success', 'Votre personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/competence.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'competences' => $availableCompetences,
        ]);
    }

    /**
     * Exporte la fiche d'un personnage.
     */
    #[Route('/{personnage}/export', name: 'export')]
    public function exportAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $participant = $personnage->getParticipants()->last();
        $groupe = null;

        if ($participant && $participant->getGroupeGn()) {
            $groupe = $participant->getGroupeGn()->getGroupe();
        }

        return $this->render('personnage/print.twig', [
            'personnage' => $personnage,
            'participant' => $participant,
            'langueMateriel' => $this->getLangueMateriel($personnage),
            'groupe' => $groupe,
        ]);
    }

    /**
     * Ajoute un evenement de chronologie au personnage.
     */
    #[Route('/{personnage}/addChronologie', name: 'admin.add.chronologie')]
    public function adminAddChronologieAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $personnageChronologie = new PersonnageChronologie();
        $personnageChronologie->setPersonnage($personnage);

        $form = $this->createForm(PersonnageChronologieForm::class, $personnageChronologie)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider l\'évènement']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $anneeGN = $form->get('annee')->getData();
            $evenement = $form->get('evenement')->getData();

            $personnageChronologie = new PersonnageChronologie();

            $personnageChronologie->setAnnee($anneeGN);
            $personnageChronologie->setEvenement($evenement);
            $personnageChronologie->setPersonnage($personnage);

            $entityManager->persist($personnageChronologie);
            $entityManager->flush();

            $this->addFlash('success', 'L\'évènement a été ajouté à la chronologie.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/updateChronologie.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'personnageChronologie' => $personnageChronologie,
        ]);
    }

    /**
     * Retire un évènement d'un personnage.
     */
    #[Route('/{personnage}/deleteChronologie', name: 'admin.delete.chronologie')]
    public function adminDeleteChronologieAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $personnageChronologie = $request->get('personnageChronologie');

        $form = $this->createForm()
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Retirer l\'évènement']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $entityManager->remove($personnageChronologie);
            $entityManager->flush();

            $this->addFlash('success', 'L\'évènement a été supprimé de la chronologie.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/removeChronologie.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'personnageChronologie' => $personnageChronologie,
        ]);
    }

    /**
     * Ajoute une lignée au personnage.
     */
    #[Route('/{personnage}/addLignee', name: 'admin.add.lignee')]
    public function adminAddLigneeAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $personnageLignee = new PersonnageLignee();
        $personnageLignee->setPersonnage($personnage);

        $form = $this->createForm(PersonnageLigneeForm::class, $personnageLignee)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider les modifications']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $parent1 = $form->get('parent1')->getData();
            $parent2 = $form->get('parent2')->getData();
            $lignee = $form->get('lignee')->getData();

            $personnageLignee->setParent1($parent1);
            $personnageLignee->setParent2($parent2);
            $personnageLignee->setLignee($lignee);

            $entityManager->persist($personnageLignee);
            $entityManager->flush();

            $this->addFlash('success', 'La lignée a été ajoutée.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/updateLignee.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'lignee' => $personnageLignee,
        ]);
    }

    /**
     * Retire une lignée d'un personnage.
     */
    #[Route('/{personnage}/deleteLignee', name: 'admin.delete.lignee')]
    public function adminDeleteLigneeAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Personnage $personnage)
    {
        $personnageLignee = $request->get('personnageLignee');

        $form = $this->createForm()
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Retirer la lignée']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $entityManager->remove($personnageLignee);
            $entityManager->flush();

            $this->addFlash('success', 'La lignée a été retirée.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/removeLignee.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'personnageLignee' => $personnageLignee,
        ]);
    }
}
