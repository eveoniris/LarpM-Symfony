<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\GnChronologieService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Chronologie des GN (opus, inter-jeux, jalons publics). Bêta : comptes ayant activé la nouvelle interface.
 */
final class GnChronologieController extends AbstractController
{
    #[Route('/gn-chronologie', name: 'gn.chronologie')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function indexAction(GnChronologieService $chronologieService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->isUiBeta()) {
            throw $this->createNotFoundException();
        }

        return $this->render('gn_chronologie/index.twig', $chronologieService->construire());
    }
}
