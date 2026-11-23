<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\LogAction;
use App\Entity\User;
use App\Enum\LogActionType;
use App\Security\IpBlocklist;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Blocage d'adresses IP par un administrateur, depuis les pages qui affichent une IP.
 */
#[IsGranted('ROLE_ADMIN')]
final class IpBloqueeController extends AbstractController
{
    public const string CSRF_ID = 'ip_bloquer';

    /** Durées proposées, en jours. */
    public const array DUREES = [1 => '24 heures', 7 => '7 jours', 30 => '30 jours', 365 => '1 an'];

    #[Route('/admin/ip-bloquees/bloquer', name: 'admin.ip.bloquer', methods: ['POST'])]
    public function bloquerAction(Request $request, IpBlocklist $ipBlocklist, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->isCsrfTokenValid(self::CSRF_ID, $request->request->getString('_token'))) {
            throw new AccessDeniedHttpException('Jeton CSRF invalide.');
        }

        $user = $this->getUser();
        $ip = trim($request->request->getString('ip'));
        $days = $request->request->getInt('days', IpBlocklist::DEFAULT_DAYS);
        $motif = trim($request->request->getString('motif'));
        $motif = '' === $motif ? null : mb_substr($motif, 0, 255);

        try {
            $entry = $ipBlocklist->block($ip, $user instanceof User ? $user : null, $motif, \array_key_exists($days, self::DUREES) ? $days : IpBlocklist::DEFAULT_DAYS, $request->getClientIp());

            $logAction = new LogAction();
            $logAction->setDate(new DateTime());
            $logAction->setType(LogActionType::IP_BLOCAGE);
            $logAction->setUser($user instanceof User ? $user : null);
            $logAction->setData([
                'cidr' => $entry->getCidr(),
                'motif' => $motif,
                'jours' => $days,
                'expire' => $entry->getExpiresAt()->format('Y-m-d H:i'),
            ]);
            $entityManager->persist($logAction);
            $entityManager->flush();

            $this->addFlash('success', \sprintf('Adresse %s bloquée jusqu\'au %s.', $entry->getCidr(), $entry->getExpiresAt()->format('d/m/Y H:i')));
        } catch (InvalidArgumentException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirect($this->safeRedirect($request->request->getString('redirect')));
    }

    /** Retour sur la page d'origine, limité aux chemins du site (pas de redirection ouverte). */
    private function safeRedirect(string $target): string
    {
        if (str_starts_with($target, '/') && !str_starts_with($target, '//') && !str_contains($target, '\\')) {
            return $target;
        }

        return $this->generateUrl('admin.connexions');
    }
}
