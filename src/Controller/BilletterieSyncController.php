<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Billet;
use App\Entity\BilletSync;
use App\Entity\Gn;
use App\Entity\User;
use App\Enum\SyncEtat;
use App\Repository\BilletSyncRepository;
use App\Service\HelloAsso\BilletSyncService;
use App\Service\HelloAsso\PlusBilletterieException;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Synchronisation de la billetterie HelloAsso Plus Billetterie et tableau de rapprochement (bêta, super admin).
 */
#[Route('/gn/{gn}/billetterie', name: 'gn.billetterie.')]
#[IsGranted('ROLE_SUPER_ADMIN')]
final class BilletterieSyncController extends SymfonyAbstractController
{
    public function __construct(
        private readonly BilletSyncService $syncService,
        private readonly BilletSyncRepository $billetSyncRepository,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%helloasso.plus.enabled%')]
        private readonly bool $enabled,
    ) {
    }

    /**
     * Traite un lot de participants HelloAsso. Appelé en boucle par le navigateur (anti-timeout).
     */
    #[Route('/sync', name: 'sync', methods: ['POST'])]
    public function syncAction(Request $request, #[MapEntity] Gn $gn): JsonResponse
    {
        $this->assertEnabled();
        if (!$this->isCsrfTokenValid('billetterie_sync_' . $gn->getId(), (string) $request->request->get('_token'))) {
            return new JsonResponse(['erreur' => 'Jeton CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $skip = max(0, $request->request->getInt('skip'));

        try {
            $result = $this->syncService->syncBatch($gn, $skip);
        } catch (PlusBilletterieException|InvalidArgumentException $exception) {
            return new JsonResponse(['erreur' => $exception->getMessage()], Response::HTTP_BAD_GATEWAY);
        }

        return new JsonResponse($result);
    }

    #[Route('/rapprochement', name: 'rapprochement', methods: ['GET'])]
    public function rapprochementAction(Request $request, #[MapEntity] Gn $gn): Response
    {
        $this->assertEnabled();

        $etat = SyncEtat::tryFrom((string) $request->query->get('etat', SyncEtat::A_VALIDER->value));

        return $this->render('gn/billetterie_rapprochement.twig', [
            'gn' => $gn,
            'etat' => $etat,
            'etats' => SyncEtat::cases(),
            'syncs' => $this->billetSyncRepository->findByGnAndEtat($gn, $etat),
            'billets' => $this->entityManager->getRepository(Billet::class)->findBy(['gn' => $gn], ['label' => 'ASC']),
        ]);
    }

    #[Route('/rapprochement/{sync}/valider', name: 'valider', methods: ['POST'])]
    public function validerAction(Request $request, #[MapEntity] Gn $gn, #[MapEntity(id: 'sync')] BilletSync $sync): RedirectResponse
    {
        $this->assertEnabled();
        $this->assertSyncOfGn($gn, $sync);
        $this->assertCsrf($request, $gn);

        // Correction manuelle éventuelle de l'utilisateur et du billet avant validation.
        $userId = trim((string) $request->request->get('user_id', ''));
        if ('' !== $userId) {
            $user = ctype_digit($userId) ? $this->entityManager->getRepository(User::class)->find((int) $userId) : null;
            if (!$user instanceof User) {
                $this->addFlash('error', "Utilisateur #{$userId} introuvable.");

                return $this->redirectToRapprochement($gn);
            }
            $sync->setUser($user);
        }

        $billetId = $request->request->getInt('billet_id');
        if ($billetId > 0) {
            $billet = $this->entityManager->getRepository(Billet::class)->findOneBy(['id' => $billetId, 'gn' => $gn]);
            if (!$billet instanceof Billet) {
                $this->addFlash('error', 'Billet introuvable pour ce GN.');

                return $this->redirectToRapprochement($gn);
            }
            $sync->setBillet($billet);
        }

        try {
            $this->syncService->validate($sync, $this->getUser() instanceof User ? $this->getUser() : null);
            $this->addFlash('success', 'Billet attribué à ' . ($sync->getEmail() ?? $sync->getAttendeeId()) . '.');
        } catch (InvalidArgumentException $exception) {
            $this->entityManager->flush();
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRapprochement($gn);
    }

    #[Route('/rapprochement/valider-tout', name: 'valider_tout', methods: ['POST'])]
    public function validerToutAction(Request $request, #[MapEntity] Gn $gn): RedirectResponse
    {
        $this->assertEnabled();
        $this->assertCsrf($request, $gn);

        $count = $this->syncService->validateAllSure($gn, $this->getUser() instanceof User ? $this->getUser() : null);
        $this->addFlash('success', $count . ' billet(s) attribué(s).');

        return $this->redirectToRapprochement($gn);
    }

    #[Route('/rapprochement/{sync}/ignorer', name: 'ignorer', methods: ['POST'])]
    public function ignorerAction(Request $request, #[MapEntity] Gn $gn, #[MapEntity(id: 'sync')] BilletSync $sync): RedirectResponse
    {
        $this->assertEnabled();
        $this->assertSyncOfGn($gn, $sync);
        $this->assertCsrf($request, $gn);

        $this->syncService->ignore($sync);
        $this->addFlash('success', 'Ligne ignorée.');

        return $this->redirectToRapprochement($gn);
    }

    private function assertEnabled(): void
    {
        if (!$this->enabled) {
            throw new NotFoundHttpException();
        }
    }

    private function assertSyncOfGn(Gn $gn, BilletSync $sync): void
    {
        if ($sync->getGn()?->getId() !== $gn->getId()) {
            throw new NotFoundHttpException();
        }
    }

    private function assertCsrf(Request $request, Gn $gn): void
    {
        if (!$this->isCsrfTokenValid('billetterie_sync_' . $gn->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }

    private function redirectToRapprochement(Gn $gn): RedirectResponse
    {
        return $this->redirectToRoute('gn.billetterie.rapprochement', ['gn' => $gn->getId()], 303);
    }
}
