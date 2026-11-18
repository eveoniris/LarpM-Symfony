<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\LoginHistory;
use App\Entity\User;
use App\Enum\LoginMethod;
use App\Repository\LoginHistoryRepository;
use DateTimeImmutable;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
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
    private const int ADMIN_LIMIT = 50;

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
            if (!$this->isNewDevice($entry, $firstIds, $oldestSuccessId)) {
                continue;
            }

            $newDeviceIds[] = $entry->getId();
        }

        return $this->render('user/connexions.twig', [
            'user' => $user,
            'isSelf' => $isSelf,
            'entries' => $entries,
            'newDeviceIds' => $newDeviceIds,
            'limit' => self::LIMIT,
        ]);
    }

    /** Vue globale des connexions, réservée aux administrateurs (ORGA exclu : l'IP est une donnée sensible). */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/connexions', name: 'admin.connexions')]
    public function adminListAction(Request $request, LoginHistoryRepository $repository): Response
    {
        $query = $request->query;
        $result = $query->getString('result');
        $limit = $query->getInt('limit', self::ADMIN_LIMIT);
        $filters = [
            'q' => trim($query->getString('q')),
            'result' => \in_array($result, ['success', 'failure'], strict: true) ? $result : '',
            'method' => LoginMethod::tryFrom($query->getString('method')),
            'reason' => $query->getString('reason'),
            'ip' => trim($query->getString('ip')),
            'from' => $this->parseDay($query->getString('from')),
            'to' => $this->parseDay($query->getString('to'))?->modify('+1 day'),
        ];

        return $this->render('admin/connexions.twig', [
            'paginator' => $repository->searchPaginated($filters, $query->getInt('page', 1), \in_array($limit, [25, 50, 100], strict: true) ? $limit : self::ADMIN_LIMIT),
            'reasons' => $repository->findDistinctFailureReasons(),
            'methods' => LoginMethod::cases(),
            'filters' => [
                'q' => $filters['q'],
                'result' => $filters['result'],
                'method' => null === $filters['method'] ? '' : $filters['method']->value,
                'reason' => $filters['reason'],
                'ip' => $filters['ip'],
                'from' => $query->getString('from'),
                'to' => $query->getString('to'),
            ],
        ]);
    }

    private function parseDay(string $value): ?DateTimeImmutable
    {
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return false === $day ? null : $day;
    }

    /** @param array<string, int> $firstIds */
    private function isNewDevice(LoginHistory $entry, array $firstIds, ?int $oldestSuccessId): bool
    {
        $fingerprint = $entry->getFingerprint();

        return $entry->isSuccess() && null !== $fingerprint && ($firstIds[$fingerprint] ?? null) === $entry->getId() && $entry->getId() !== $oldestSuccessId;
    }
}
