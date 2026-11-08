<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Entity\ApiClientRequest;
use App\Repository\UserRepository;
use App\Service\MailService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Prévient les admins d'une nouvelle demande d'application tierce (message interne et e-mail).
 */
final readonly class ApiClientRequestNotifier
{
    public function __construct(
        private UserRepository $users,
        private MailService $mailService,
        private UrlGeneratorInterface $router,
    ) {
    }

    public function notifyAdmins(ApiClientRequest $demande): void
    {
        $lien = $this->router->generate('api.client.admin.requests', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $texte = \sprintf('%s demande une application API « %s » (lecture seule). Traiter la demande : %s', $demande->getRequester()->getDisplayName(), $demande->getName(), $lien);

        foreach ($this->users->findAdmins() as $admin) {
            // MailService::newMessage crée le message interne et envoie l'e-mail.
            $this->mailService->newMessage($admin, $texte, 'Demande d\'application API', $demande->getRequester());
        }
    }
}
