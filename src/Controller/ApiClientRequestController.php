<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ApiClientRequest;
use App\Entity\User;
use App\Repository\ApiClientRepository;
use App\Repository\ApiClientRequestRepository;
use App\Repository\GnRepository;
use App\Service\Api\ApiClientRequestNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Demande de création d'une application tierce (lecture seule) par un utilisateur connecté.
 */
#[IsGranted('ROLE_USER')]
final class ApiClientRequestController extends AbstractController
{
    private const int MAX_EN_ATTENTE = 3;

    #[Route('/applications-api/demande', name: 'api.client.request', methods: ['GET', 'POST'])]
    public function requestAction(
        Request $request,
        ApiClientRequestRepository $requests,
        ApiClientRepository $clients,
        GnRepository $gns,
        ApiClientRequestNotifier $notifier,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $errors = [];
        $form = ['name' => '', 'purpose' => '', 'gn' => ''];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('api_client_request', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }
            $form = [
                'name' => trim($request->request->getString('name')),
                'purpose' => trim($request->request->getString('purpose')),
                'gn' => $request->request->getString('gn'),
            ];

            if (1 !== preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]{1,59}$/', $form['name'])) {
                $errors[] = 'Le nom (2 à 60 caractères) ne contient que lettres, chiffres, tiret et underscore.';
            } elseif (null !== $clients->findOneBy(['name' => $form['name']])) {
                $errors[] = 'Ce nom d\'application est déjà pris.';
            }
            if (mb_strlen($form['purpose']) < 10 || mb_strlen($form['purpose']) > 2000) {
                $errors[] = 'Décrivez l\'usage prévu (10 à 2000 caractères).';
            }
            $gn = null;
            if ('' !== $form['gn']) {
                $gn = ctype_digit($form['gn']) ? $gns->find((int) $form['gn']) : null;
                if (null === $gn) {
                    $errors[] = 'GN introuvable.';
                }
            }
            if ($requests->countPendingByRequester($user) >= self::MAX_EN_ATTENTE) {
                $errors[] = 'Vous avez déjà ' . self::MAX_EN_ATTENTE . ' demandes en attente.';
            }

            if ([] === $errors) {
                $demande = new ApiClientRequest($user, $form['name'], $form['purpose'], $gn);
                $requests->save($demande);
                $notifier->notifyAdmins($demande);

                $this->addFlash('success', 'Votre demande est transmise aux administrateurs.');

                return $this->redirectToRoute('api.client.request');
            }
        }

        return $this->render('api_client/request/form.twig', [
            'errors' => $errors,
            'form' => $form,
            'gns' => $gns->findBy([], ['id' => 'DESC']),
            'mine' => $requests->findByRequester($user),
        ]);
    }
}
