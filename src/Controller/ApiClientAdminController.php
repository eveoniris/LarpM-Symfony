<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ApiClient;
use App\Repository\ApiClientRepository;
use App\Repository\ApiClientRequestRepository;
use App\Repository\GnRepository;
use App\Service\Api\ApiClientManager;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Administration des applications tierces de l'API : liste, création (secret affiché une seule fois), révocation.
 * ROLE_API_ADMIN (tous les GN) n'est attribuable que par un super admin.
 */
#[IsGranted('ROLE_ADMIN')]
final class ApiClientAdminController extends AbstractController
{
    private const array ROLES_ATTRIBUABLES = ['ROLE_API_READ', 'ROLE_API_ADMIN'];

    #[Route('/admin/api-clients', name: 'api.client.admin', methods: ['GET'])]
    public function listAction(ApiClientRepository $clients, ApiClientRequestRepository $requests): Response
    {
        return $this->render('api_client/admin/list.twig', [
            'clients' => $clients->findBy([], ['createdAt' => 'DESC']),
            'pendingRequests' => \count($requests->findPending()),
        ]);
    }

    #[Route('/admin/api-clients/new', name: 'api.client.admin.new', methods: ['GET', 'POST'])]
    public function newAction(Request $request, ApiClientManager $manager, GnRepository $gns): Response
    {
        $errors = [];
        $form = ['name' => '', 'role' => 'ROLE_API_READ', 'gn' => ''];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('api_client_new', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }
            $form = [
                'name' => trim($request->request->getString('name')),
                'role' => $request->request->getString('role'),
                'gn' => $request->request->getString('gn'),
            ];

            if (1 !== preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]{1,59}$/', $form['name'])) {
                $errors[] = 'Le nom (2 à 60 caractères) ne contient que lettres, chiffres, tiret et underscore.';
            }
            if (!\in_array($form['role'], self::ROLES_ATTRIBUABLES, strict: true)) {
                $errors[] = 'Rôle inconnu.';
            } elseif ('ROLE_API_ADMIN' === $form['role'] && !$this->isGranted('ROLE_SUPER_ADMIN')) {
                $errors[] = 'Seul un super admin peut créer une application ROLE_API_ADMIN.';
            }
            $gn = null;
            if ('' !== $form['gn']) {
                $gn = ctype_digit($form['gn']) ? $gns->find((int) $form['gn']) : null;
                if (null === $gn) {
                    $errors[] = 'GN introuvable.';
                }
            }

            if ([] === $errors) {
                try {
                    $result = $manager->create($form['name'], [$form['role']], $gn);

                    // Pas de redirection : le secret n'est affiché qu'une seule fois, dans cette réponse.
                    return $this->render('api_client/admin/created.twig', [
                        'client' => $result['client'],
                        'secret' => $result['secret'],
                    ]);
                } catch (InvalidArgumentException $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        return $this->render('api_client/admin/new.twig', [
            'errors' => $errors,
            'form' => $form,
            'gns' => $gns->findBy([], ['id' => 'DESC']),
            'canApiAdmin' => $this->isGranted('ROLE_SUPER_ADMIN'),
        ]);
    }

    #[Route('/admin/api-clients/{id}/revoke', name: 'api.client.admin.revoke', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function revokeAction(Request $request, ApiClient $client, ApiClientManager $manager): Response
    {
        if (!$this->isCsrfTokenValid('api_client_revoke_' . $client->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        $manager->revoke($client);
        $this->addFlash('success', 'L\'application « ' . $client->getName() . ' » est révoquée.');

        return $this->redirectToRoute('api.client.admin');
    }
}
