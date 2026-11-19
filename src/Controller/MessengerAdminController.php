<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\MessengerSurveillance;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Surveillance de la file d'envoi des e-mails : volume en attente, messages en échec, rejeu et suppression.
 */
#[IsGranted('ROLE_ADMIN')]
final class MessengerAdminController extends AbstractController
{
    #[Route('/admin/messenger', name: 'admin.messenger', methods: ['GET'])]
    public function indexAction(MessengerSurveillance $surveillance): Response
    {
        return $this->render('admin/messenger.twig', [
            'echecs' => $surveillance->echecs(),
            'total' => $surveillance->nombreEchecs(),
            'attente' => $surveillance->enAttente(),
        ]);
    }

    #[Route('/admin/messenger/{id}/{action}', name: 'admin.messenger.action', requirements: ['action' => 'rejouer|supprimer'], methods: ['POST'])]
    public function actionAction(Request $request, MessengerSurveillance $surveillance, string $id, string $action): Response
    {
        if (!$this->isCsrfTokenValid('messenger_' . $id, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $fait = 'rejouer' === $action ? $surveillance->rejouer($id) : $surveillance->supprimer($id);
        $succes = 'rejouer' === $action ? 'Message remis dans la file d\'envoi.' : 'Message supprimé.';
        $this->addFlash($fait ? 'success' : 'warning', $fait ? $succes : 'Message introuvable (déjà traité ?).');

        return $this->redirectToRoute('admin.messenger');
    }
}
