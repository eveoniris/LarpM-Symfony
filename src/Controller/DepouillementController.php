<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Dépouillement des enveloppes de retour de jeu (joueur et groupe).
 *
 * Fonctionnalité en cours de développement : réservée à ROLE_BETA_TEST (voir docs/plans/role-beta-test.md).
 */
#[IsGranted('ROLE_BETA_TEST', message: 'Fonctionnalité en cours de test.')]
#[Route('/depouillement')]
final class DepouillementController extends AbstractController
{
    #[Route('', name: 'depouillement.index', methods: ['GET'])]
    public function indexAction(): Response
    {
        return $this->render('depouillement/index.twig');
    }
}
