<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Conception;
use App\Entity\Gn;
use App\Exception\ConceptionInvalideException;
use App\Form\Depouillement\HeritiersType;
use App\Repository\ConceptionRepository;
use App\Security\Voter\DepouillementVoter;
use App\Service\HeritierSaisie;
use App\Service\HeritierService;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Écran du scénariste : conceptions d'héritier signalées dans les retours de jeu, et décision (créer l'héritier, gérer plus
 * tard, clôturer). Réservé aux rôles qui valident (voir DepouillementVoter).
 *
 * Fonctionnalité en cours de développement : réservée à ROLE_BETA_TEST (voir docs/plans/role-beta-test.md).
 */
#[IsGranted('ROLE_BETA_TEST', message: 'Fonctionnalité en cours de test.')]
final class DepouillementHeritierController extends AbstractDepouillementController
{
    #[Route('/gn/{gn}/conceptions', name: 'depouillement.conceptions', requirements: ['gn' => '\d+'], methods: ['GET'])]
    public function indexAction(Gn $gn, ConceptionRepository $conceptionRepository, FormFactoryInterface $formFactory): Response
    {
        $this->denyAccessUnlessGranted(DepouillementVoter::VALIDER, $gn);

        return $this->ecran($gn, $conceptionRepository, $formFactory);
    }

    // @mago-expect lint:excessive-parameter-list
    #[Route('/gn/{gn}/conceptions/{conception}/heritiers', name: 'depouillement.conception.heritiers', requirements: ['gn' => '\d+', 'conception' => '\d+'], methods: ['POST'])]
    public function creerAction(
        Request $request,
        Gn $gn,
        Conception $conception,
        HeritierService $heritierService,
        ConceptionRepository $conceptionRepository,
        FormFactoryInterface $formFactory,
    ): Response {
        $this->verifierConception($gn, $conception);

        $form = $this->formulaire($formFactory, $conception);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{premier: HeritierSaisie, second: HeritierSaisie} $donnees */
            $donnees = $form->getData();
            $saisies = array_values(array_filter([$donnees['premier'], $donnees['second']], static fn (HeritierSaisie $saisie): bool => '' !== trim($saisie->nom)));

            try {
                $heritiers = $heritierService->creer($conception, $saisies, $this->utilisateur());
            } catch (ConceptionInvalideException $exception) {
                $this->addFlash('error', $exception->getMessage());

                return $this->ecran($gn, $conceptionRepository, $formFactory, $form, $conception);
            }

            $this->addFlash('success', \sprintf(
                '%d héritier(s) créé(s) : %s.',
                \count($heritiers),
                implode(', ', array_map(static fn ($heritier): string => $heritier->getNom() . ' (n°' . $heritier->getId() . ')', $heritiers)),
            ));

            return $this->redirectToRoute('depouillement.conceptions', ['gn' => $gn->getId()], 303);
        }

        $this->flashErreurs($form);

        return $this->ecran($gn, $conceptionRepository, $formFactory, $form, $conception);
    }

    #[Route('/gn/{gn}/conceptions/{conception}/plus-tard', name: 'depouillement.conception.plus_tard', requirements: ['gn' => '\d+', 'conception' => '\d+'], methods: ['POST'])]
    public function plusTardAction(Request $request, Gn $gn, Conception $conception, HeritierService $heritierService): Response
    {
        return $this->decision(
            $request,
            $gn,
            $conception,
            static fn (string $remarque, $utilisateur) => $heritierService->gererPlusTard($conception, $remarque, $utilisateur),
            'Création gérée plus tard : choix consigné.',
        );
    }

    #[Route('/gn/{gn}/conceptions/{conception}/cloturer', name: 'depouillement.conception.cloturer', requirements: ['gn' => '\d+', 'conception' => '\d+'], methods: ['POST'])]
    public function cloturerAction(Request $request, Gn $gn, Conception $conception, HeritierService $heritierService): Response
    {
        return $this->decision(
            $request,
            $gn,
            $conception,
            static fn (string $remarque, $utilisateur) => $heritierService->cloturer($conception, $remarque, $utilisateur),
            'Conception clôturée : décision consignée.',
        );
    }

    /**
     * @param callable(string, \App\Entity\User): void $action
     */
    private function decision(Request $request, Gn $gn, Conception $conception, callable $action, string $succes): Response
    {
        $this->verifierConception($gn, $conception);
        $retour = $this->redirectToRoute('depouillement.conceptions', ['gn' => $gn->getId()], 303);

        if (!$this->jetonValide($request, 'conception_decision_' . $conception->getId())) {
            return $retour;
        }

        try {
            $action($request->request->getString('remarque'), $this->utilisateur());
        } catch (ConceptionInvalideException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $retour;
        }

        $this->addFlash('success', $succes);

        return $retour;
    }

    private function verifierConception(Gn $gn, Conception $conception): void
    {
        if ($conception->getGn()->getId() !== $gn->getId()) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(DepouillementVoter::VALIDER, $conception);
    }

    private function formulaire(FormFactoryInterface $formFactory, Conception $conception): FormInterface
    {
        return $formFactory->createNamed(
            'heritiers_' . $conception->getId(),
            HeritiersType::class,
            ['premier' => new HeritierSaisie(), 'second' => new HeritierSaisie()],
            ['action' => $this->generateUrl('depouillement.conception.heritiers', ['gn' => $conception->getGn()->getId(), 'conception' => $conception->getId()])],
        );
    }

    private function ecran(Gn $gn, ConceptionRepository $conceptionRepository, FormFactoryInterface $formFactory, ?FormInterface $formEnErreur = null, ?Conception $conceptionEnErreur = null): Response
    {
        $conceptions = $conceptionRepository->findParGn($gn);
        $formulaires = [];
        foreach ($conceptions as $conception) {
            if (!$conception->getStatut()->estEnAttente() || $conception->isSimulation()) {
                continue;
            }

            $form = null !== $formEnErreur && $conception->getId() === $conceptionEnErreur?->getId() ? $formEnErreur : $this->formulaire($formFactory, $conception);
            $formulaires[$conception->getId()] = $form->createView();
        }

        return $this->render('depouillement/conceptions.twig', ['gn' => $gn, 'conceptions' => $conceptions, 'formulaires' => $formulaires]);
    }
}
