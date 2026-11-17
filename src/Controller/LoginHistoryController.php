<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\LoginHistory;
use App\Entity\User;
use App\Repository\LoginHistoryRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Historique des connexions d'un compte : visible par son propriétaire (« Mes connexions ») et par les administrateurs.
 */
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class LoginHistoryController extends AbstractController
{
    private const int LIMIT = 100;

    #[Route('/user/{user}/connexions', name: 'user.connexions', requirements: ['user' => Requirement::DIGITS])]
    public function listAction(#[MapEntity] User $user, LoginHistoryRepository $repository): Response
    {
        $current = $this->getUser();
        $isSelf = $current instanceof User && $current->getId() === $user->getId();
        if (!$isSelf && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Historique de connexion réservé au propriétaire du compte et aux administrateurs.');
        }

        $entries = $repository->findLatestByUser($user, self::LIMIT);
        $firstIds = $repository->firstSuccessIdByFingerprint($user);
        $oldestSuccessId = [] === $firstIds ? null : min($firstIds);

        $newDeviceIds = [];
        foreach ($entries as $entry) {
            if ($this->isNewDevice($entry, $firstIds, $oldestSuccessId)) {
                $newDeviceIds[] = $entry->getId();
            }
        }

        return $this->render('user/connexions.twig', [
            'user' => $user,
            'isSelf' => $isSelf,
            'entries' => $entries,
            'newDeviceIds' => $newDeviceIds,
            'limit' => self::LIMIT,
        ]);
    }

    /** @param array<string, int> $firstIds */
    private function isNewDevice(LoginHistory $entry, array $firstIds, ?int $oldestSuccessId): bool
    {
        $fingerprint = $entry->getFingerprint();

        return $entry->isSuccess() && null !== $fingerprint && ($firstIds[$fingerprint] ?? null) === $entry->getId() && $entry->getId() !== $oldestSuccessId;
    }
}
