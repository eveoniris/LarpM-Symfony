<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ApiClientRequest;
use App\Entity\User;
use App\Repository\ApiClientRequestRepository;
use App\Service\Api\ApiClientManager;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Traitement par un admin des demandes d'applications tierces.
 */
#[IsGranted('ROLE_ADMIN')]
final class ApiClientRequestAdminController extends AbstractController
{
    #[Route('/admin/api-clients/demandes', name: 'api.client.admin.requests', methods: ['GET'])]
    public function listAction(ApiClientRequestRepository $requests): Response
    {
        return $this->render('api_client/request/admin_list.twig', [
            'pending' => $requests->findPending(),
            'done' => $requests->findBy(['status' => [ApiClientRequest::ACCEPTEE, ApiClientRequest::REFUSEE]], ['decidedAt' => 'DESC'], 20),
        ]);
    }

    #[Route('/admin/api-clients/demandes/{id}/accept', name: 'api.client.admin.request.accept', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function acceptAction(
        Request $request,
        ApiClientRequest $demande,
        ApiClientManager $manager,
        EntityManagerInterface $entityManager,
        MailService $mailService,
    ): Response {
        $admin = $this->decisionPrerequisites($request, $demande, 'api_client_request_accept_');

        try {
            $result = $manager->create($demande->getName(), ['ROLE_API_READ'], $demande->getGn());
        } catch (InvalidArgumentException $e) {
            $this->addFlash('alert', $e->getMessage());

            return $this->redirectToRoute('api.client.admin.requests');
        }
        $demande->accept($admin);
        $entityManager->flush();

        $mailService->newMessage(
            $demande->getRequester(),
            \sprintf('Votre demande d\'application API « %s » est acceptée. Un administrateur vous transmettra ses identifiants (client_id et secret).', $demande->getName()),
            'Application API acceptée',
            $admin,
        );

        // Pas de redirection : le secret n'est affiché qu'une seule fois, dans cette réponse.
        return $this->render('api_client/admin/created.twig', [
            'client' => $result['client'],
            'secret' => $result['secret'],
            'requester' => $demande->getRequester(),
        ]);
    }

    #[Route('/admin/api-clients/demandes/{id}/refuse', name: 'api.client.admin.request.refuse', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function refuseAction(
        Request $request,
        ApiClientRequest $demande,
        EntityManagerInterface $entityManager,
        MailService $mailService,
    ): Response {
        $admin = $this->decisionPrerequisites($request, $demande, 'api_client_request_refuse_');
        $note = trim($request->request->getString('note'));
        $demande->refuse($admin, '' === $note ? null : mb_substr($note, 0, 1000));
        $entityManager->flush();

        $mailService->newMessage(
            $demande->getRequester(),
            \sprintf('Votre demande d\'application API « %s » est refusée.%s', $demande->getName(), '' === $note ? '' : ' Motif : ' . $note),
            'Application API refusée',
            $admin,
        );
        $this->addFlash('success', 'La demande est refusée.');

        return $this->redirectToRoute('api.client.admin.requests');
    }

    private function decisionPrerequisites(Request $request, ApiClientRequest $demande, string $tokenPrefix): User
    {
        if (!$this->isCsrfTokenValid($tokenPrefix . $demande->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        if (!$demande->isPending()) {
            throw $this->createNotFoundException('Demande déjà traitée.');
        }
        $admin = $this->getUser();
        if (!$admin instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $admin;
    }
}
