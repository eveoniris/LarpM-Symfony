<?php

namespace App\Controller;

use App\Entity\Annonce;
use App\Entity\EtatCivil;
use App\Entity\Gn;
use App\Entity\Langue;
use App\Entity\Restriction;
use App\Entity\Territoire;
use App\Form\EtatCivilForm;
use App\Form\UserRestrictionForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    public function indexAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        if (!$this->getUser()) {
            return $this->notConnectedIndexAction();
        }

        if (!$this->getUser()->getEtatCivil()) {
            return $this->redirectToRoute('newUser.step1', [], 303);
        }

        $repoAnnonce = $entityManager->getRepository(Annonce::class);
        $annonces = $repoAnnonce->findBy(['archive' => false, 'gn' => null], ['update_date' => 'DESC']);

        return $this->render('homepage/index.twig', [
            'annonces' => $annonces,
            'user' => $this->getUser(),
        ]);
    }

    /**
     * Page d'acceuil pour les utilisateurs non connecté.
     */
    public function notConnectedIndexAction(): Response
    {
        return $this->render('homepage/not_connected.twig');
    }

    /**
     * Première étape pour un nouvel utilisateur.
     */
    // TODO : migrate to UserController?
    #[Route('/user/new/step1', name: 'user.new-step1')]
    public function newUserStep1Action(EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser()?->getEtatCivil()) {
            $repoAnnonce = $entityManager->getRepository(Annonce::class);
            $annonces = $repoAnnonce->findBy(['archive' => false, 'gn' => null], ['update_date' => 'DESC']);

            return $this->render('homepage/index.twig', [
                'annonces' => $annonces,
                'user' => $this->getUser(),
            ]);
        }

        // Todo : in homepage/new-user/step1 or in user/new/step
        return $this->render('newUser/step1.twig', []);
    }

    /**
     * Seconde étape pour un nouvel utilisateur : enregistrer les informations administratives.
     */
    #[Route('/user/new/step2', name: 'user.new-step2')]
    public function newUserStep2Action(Request $request): RedirectResponse|Response
    {
        $etatCivil = $this->getUser()?->getEtatCivil();
        if (!$etatCivil) {
            $etatCivil = new EtatCivil();
        }

        $form = $this->createForm(EtatCivilForm::class, $etatCivil);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $etatCivil = $form->getData();
            $this->getUser()->setEtatCivil($etatCivil);

            $this->entityManager->persist($this->getUser());
            $this->entityManager->flush();

            return $this->redirectToRoute('newUser.step3', [], 303);
        }

        return $this->render('newUser/step2.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Troisième étape pour un nouvel utilisateur : les restrictions alimentaires.
     */
    public function newUserStep3Action(Request $request, EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(UserRestrictionForm::class, $this->getUser());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $User = $form->getData();
            $newRestriction = $form->get('new_restriction')->getData();
            if ($newRestriction) {
                $restriction = new Restriction();
                $restriction->setUserRelatedByAuteurId($this->getUser());
                $restriction->setLabel($newRestriction);

                $entityManager->persist($restriction);
                $User->addRestriction($restriction);
            }

            $entityManager->persist($User);
            $entityManager->flush();

            return $this->redirectToRoute('newUser.step4', [], 303);
        }

        return $this->render('newUser/step3.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Quatrième étape pour un nouvel utilisateur : choisir un GN.
     */
    public function newUserStep4Action(Request $request, EntityManagerInterface $entityManager)
    {
        return $this->render('newUser/step4.twig');
    }

    /**
     * Affiche une carte du monde.
     */
    #[Route('/world', name: 'world')]
    public function worldAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->render('world.twig');
    }

    /**
     * Fourni la liste des pays, leur geographie et leur description.
     */
    #[Route('/world/countries.json', name: 'world.countries.json')]
    public function countriesAction(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        return $this->getWorldTerritoireGeoData('countries');
    }

    private function getWorldTerritoireGeoData(string $type): JsonResponse
    {
        $repoTerritoire = $this->entityManager->getRepository(Territoire::class);

        $territoires = match ($type) {
            'fiefs' => $repoTerritoire->findFiefs(),
            'countries' => $repoTerritoire->findRoot(),
            'regions' => $repoTerritoire->findRegions(),
            default => throw new \Exception('Unkonw territoire type : '.$type),
        };

        $data = [];
        foreach ($territoires as $territoire) {
            $data[] = $this->addGeoData($territoire);
        }

        return new JsonResponse($data);
    }

    private function addGeoData($data): array
    {
        return [
            'id' => $data->getId(),
            'geom' => $data->getGeojson(),
            'name' => $data->getNom(),
            'color' => $data->getColor(),
            'description' => strip_tags((string) $data->getDescription()),
            'groupes' => array_values($data->getGroupesPj()),
            'desordre' => $data->getStatutIndex(),
            'langue' => $data->getLanguePrincipale(),
        ];
    }

    /**
     * Fourni la liste des régions.
     */
    #[Route('/world/regions.json', name: 'world.regions.json')]
    public function regionsAction(EntityManagerInterface $entityManager)
    {
        return $this->getWorldTerritoireGeoData('regions');
    }

    /**
     * Fourni la liste des fiefs.
     */
    #[Route('/world/fiefs.json', name: 'world.fiefs.json')]
    public function fiefsAction(): JsonResponse
    {
        return $this->getWorldTerritoireGeoData('fiefs');
    }

    /**
     * Fourni la liste des langues.
     */
    public function languesAction(Request $request, EntityManagerInterface $entityManager)
    {
        $langueList = $entityManager->getRepository(Langue::class)->findAll();

        $langues = [];
        foreach ($langueList as $langue) {
            if (0 == $langue->getDiffusion()) {
                continue;
            } // ne pas transmettre aux joueurs la liste des langues anciennes

            // construction du geojson de la langue (utilisation en tant que langue principale)
            $geojson = '{ "type": "FeatureCollection", "features": [';

            $first = true;
            $geoJsonPrincipalCount = 0;
            foreach ($langue->getTerritoires() as $territoire) {
                $geom = $territoire->getGeojson();

                if ($geom) {
                    if (!$first) {
                        $geojson .= ',';
                    }

                    $geojson .= $geom;
                    $first = false;
                    ++$geoJsonPrincipalCount;
                }
            }

            $geojson .= ']}';
            $geoJsonPrincipal = $geojson;

            // construction du geoJson de la langue (utilisation en tant que langue secondaire)
            $geojson = '{ "type": "FeatureCollection", "features": [';
            $first = true;
            $geoJsonSecondaireCount = 0;
            foreach ($langue->getTerritoireSecondaires() as $territoire) {
                $geom = $territoire->getGeojson();

                if ($geom) {
                    if (!$first) {
                        $geojson .= ',';
                    }

                    $geojson .= $geom;
                    $first = false;
                    ++$geoJsonSecondaireCount;
                }
            }

            $geojson .= ']}';
            $geoJsonSecondaire = $geojson;

            $langues[] = [
                'id' => $langue->getId(),
                'label' => $langue->getLabel(),
                'description' => $langue->getDescription(),
                'diffusion' => $langue->getDiffusion(),
                'geomPrincipal' => $geoJsonPrincipal,
                'geomSecondaire' => $geoJsonSecondaire,
                'geomPrincipalCount' => $geoJsonPrincipalCount,
                'geomSecondaireCount' => $geoJsonSecondaireCount,
            ];
        }

        return new JsonResponse($langues);
    }

    /**
     * Fourni la liste des groupes.
     */
    public function groupesAction(Request $request, EntityManagerInterface $entityManager)
    {
        // recherche le prochain GN
        $gnRepo = $entityManager->getRepository(Gn::class);
        $gn = $gnRepo->findNext();

        $groupeGnList = $gn->getGroupeGns();

        $groupes = [];
        foreach ($groupeGnList as $groupeGn) {
            $groupe = $groupeGn->getGroupe();
            if (true === $groupe->getPj()) {
                $geom = null;
                if ($groupe->getTerritoires()->count() > 0) {
                    $territoire = $groupe->getTerritoires()->first();
                    $geom = $territoire->getGeojson();
                }

                // mettre en surbrillance le groupe de l'utilisateur
                $highlight = false;
                if ($this->getUser()) {
                    foreach ($this->getUser()->getParticipants() as $participant) {
                        if ($participant->getGn() == $gn && $participant->getGroupeGn()) {
                            if ($participant->getGroupeGn()->getGroupe() == $groupe) {
                                $highlight = true;
                                break;
                            }
                        }
                    }
                }

                $groupes[] = [
                    'id' => $groupe->getId(),
                    'nom' => $groupe->getNom(),
                    'geom' => $geom,
                    'highlight' => $highlight,
                ];
            }
        }

        return new JsonResponse($groupes);
    }

    /**
     * Met à jour la geographie d'un pays.
     */
    public function updateCountryGeomAction(Request $request, EntityManagerInterface $entityManager)
    {
        $territoire = $request->get('territoire');
        $geom = $request->get('geom');
        $color = $request->get('color');

        $territoire->setGeojson($geom);
        $territoire->setColor($color);

        $entityManager->persist($territoire);
        $entityManager->flush();

        $country = [
            'id' => $territoire->getId(),
            'geom' => $territoire->getGeojson(),
            'name' => $territoire->getNom(),
            'description' => strip_tags((string) $territoire->getDescription()),
            'groupes' => array_values($territoire->getGroupesPj()),
        ];

        return new JsonResponse($country);
    }

    /**
     * Affiche une page récapitulatif des liens pour discuter.
     */
    public function discuterAction(Request $request, EntityManagerInterface $entityManager)
    {
        return $this->render('discuter.twig');
    }

    /**
     * Affiche une page récapitulatif des événements.
     */
    public function evenementAction(Request $request, EntityManagerInterface $entityManager)
    {
        return $this->render('evenement.twig');
    }

    /**
     * Affiche les mentions légales.
     */
    #[Route('/legal', name: 'legal')]
    public function legalAction(Request $request, EntityManagerInterface $entityManager)
    {
        return $this->render('homepage/legal.twig');
    }

    /**
     * Affiche les informations de dev.
     */
    #[Route('/dev', name: 'dev')]
    #[When(env: 'dev')]
    public function devAction(Request $request, EntityManagerInterface $entityManager)
    {
        return $this->render('homepage/dev.twig');
    }

    /**
     * Statistiques du projet.
     */
    public function metricsAction(Request $request, EntityManagerInterface $entityManager)
    {
        return $this->render('homepage/metrics/report.html');
    }
}
