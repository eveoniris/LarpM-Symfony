<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\Discord\DiscordInteractionHandler;
use App\Service\Discord\DiscordOAuthClient;
use App\Service\Discord\DiscordOAuthException;
use App\Service\Discord\DiscordSignatureVerifier;
use App\Service\Discord\DiscordSyncService;
use Throwable;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DiscordController extends AbstractController
{
    private const string SESSION_STATE = 'discord_oauth_state';

    /**
     * Démarre la liaison du compte Discord de l'utilisateur connecté (OAuth2, scope identify).
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/discord/lier', name: 'discord.lier', methods: ['GET'])]
    public function lierAction(Request $request, DiscordOAuthClient $oauth): Response
    {
        $state = bin2hex(random_bytes(16));
        $request->getSession()->set(self::SESSION_STATE, $state);

        return $this->redirect($oauth->authorizeUrl($this->callbackUrl(), $state));
    }

    /**
     * Retour de Discord : vérifie le state, lit l'identité et l'enregistre sur le compte connecté.
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/discord/callback', name: 'discord.callback', methods: ['GET'])]
    public function callbackAction(Request $request, DiscordOAuthClient $oauth): Response
    {
        $user = $this->getUser();
        $expectedState = (string) $request->getSession()->remove(self::SESSION_STATE);
        $state = $request->query->getString('state');
        $redirect = $this->redirectToRoute('user.edit', ['user' => $user->getId()]);

        if ('' === $expectedState || !hash_equals($expectedState, $state)) {
            $this->addFlash('alert', 'La liaison Discord a expiré ou est invalide, veuillez recommencer.');

            return $redirect;
        }

        $code = $request->query->getString('code');
        if ('' === $code || $request->query->has('error')) {
            $this->addFlash('alert', 'La liaison Discord a été annulée.');

            return $redirect;
        }

        try {
            $identity = $oauth->fetchIdentity($code, $this->callbackUrl());
        } catch (DiscordOAuthException $exception) {
            $this->logger->warning('Liaison Discord échouée : ' . $exception->getMessage());
            $this->addFlash('alert', 'Impossible de contacter Discord, veuillez réessayer plus tard.');

            return $redirect;
        }

        $other = $this->entityManager->getRepository(User::class)->findOneBy(['discordId' => $identity->id]);
        if (null !== $other && $other->getId() !== $user->getId()) {
            $this->addFlash('alert', 'Ce compte Discord est déjà lié à un autre compte LarpManager.');

            return $redirect;
        }

        $user->setDiscordId($identity->id);
        if (1 === preg_match('/^[a-z0-9_.]{2,32}$/i', $identity->username)) {
            $user->setDiscordPseudo($identity->username);
        }
        $this->entityManager->flush();

        $this->addFlash('success', 'Votre compte Discord est lié.');

        return $redirect;
    }

    /**
     * Supprime la liaison (le pseudo reste, modifiable à la main).
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/discord/delier', name: 'discord.delier', methods: ['POST'])]
    public function delierAction(Request $request): Response
    {
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('discord_delier', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $user->setDiscordId(null);
        $this->entityManager->flush();
        $this->addFlash('success', 'La liaison avec Discord a été supprimée.');

        return $this->redirectToRoute('user.edit', ['user' => $user->getId()]);
    }

    /**
     * Synchronise les rôles Discord de l'utilisateur connecté dans toutes les guildes configurées.
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/discord/synchroniser', name: 'discord.synchroniser', methods: ['POST'])]
    public function synchroniserAction(Request $request, DiscordSyncService $syncService): Response
    {
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('discord_synchroniser', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if (!$user->isDiscordLinked()) {
            $this->addFlash('alert', 'Liez d\'abord votre compte Discord.');
        } else {
            $results = $syncService->syncAll($user);
            if ([] === $results) {
                $this->addFlash('alert', 'Aucun serveur Discord n\'est configuré.');
            }
            foreach ($results as $result) {
                $this->addFlash($result->isOk() ? 'success' : 'alert', $result->summary());
            }
        }

        $retour = $request->request->getString('retour');
        // Redirection interne uniquement (pas de //hote ni de schéma).
        if (str_starts_with($retour, '/') && !str_starts_with($retour, '//')) {
            return $this->redirect($retour);
        }

        return $this->redirectToRoute('user.edit', ['user' => $user->getId()]);
    }

    private function callbackUrl(): string
    {
        return $this->generateUrl('discord.callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Interactions Endpoint URL : PING, puis commande /roles. Chaque requête est authentifiée par la signature Ed25519 de Discord.
     */
    #[Route('/discord/interactions', name: 'discord.interactions', methods: ['POST'])]
    public function interactionsAction(
        Request $request,
        DiscordSignatureVerifier $verifier,
        DiscordInteractionHandler $handler,
        EventDispatcherInterface $dispatcher,
    ): Response {
        $body = $request->getContent();
        if (!$verifier->isValid($body, $request->headers->get('x-signature-ed25519'), $request->headers->get('x-signature-timestamp'))) {
            return new JsonResponse([], 401);
        }

        $payload = json_decode($body, true);
        $response = \is_array($payload) ? $handler->handle($payload) : null;
        if (null === $response) {
            return new JsonResponse([], 400);
        }

        // Le travail différé s'exécute une fois la réponse envoyée à Discord (limite de 3 s).
        if (null !== $response->afterResponse) {
            $afterResponse = $response->afterResponse;
            $dispatcher->addListener(KernelEvents::TERMINATE, function () use ($afterResponse): void {
                try {
                    $afterResponse();
                } catch (Throwable $exception) {
                    $this->logger->error('Interaction Discord différée en échec : ' . $exception->getMessage());
                }
            });
        }

        return new JsonResponse($response->body);
    }
}
