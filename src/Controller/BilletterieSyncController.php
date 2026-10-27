<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Billet;
use App\Entity\BilletSync;
use App\Entity\BilletTarif;
use App\Entity\Gn;
use App\Entity\User;
use App\Enum\SyncConfiance;
use App\Enum\SyncEtat;
use App\Repository\BilletSyncRepository;
use App\Repository\BilletTarifRepository;
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
        private readonly BilletTarifRepository $tarifRepository,
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

    /**
     * Diagnostic en lecture seule : statut HTTP de chaque route HelloAsso testée avec la clé configurée.
     */
    #[Route('/diagnostic', name: 'diagnostic', methods: ['POST'])]
    public function diagnosticAction(Request $request, #[MapEntity] Gn $gn): JsonResponse
    {
        $this->assertEnabled();
        if (!$this->isCsrfTokenValid('billetterie_sync_' . $gn->getId(), (string) $request->request->get('_token'))) {
            return new JsonResponse(['erreur' => 'Jeton CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        try {
            return new JsonResponse(['routes' => $this->syncService->diagnose($gn)]);
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(['erreur' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/rapprochement', name: 'rapprochement', methods: ['GET'])]
    public function rapprochementAction(Request $request, #[MapEntity] Gn $gn): Response
    {
        $this->assertEnabled();

        $etatParam = (string) $request->query->get('etat', SyncEtat::A_VALIDER->value);
        $etat = SyncEtat::tryFrom($etatParam);
        $confiance = SyncConfiance::tryFrom((string) $request->query->get('confiance', ''));
        $status = trim((string) $request->query->get('status', ''));
        $productId = trim((string) $request->query->get('produit', ''));
        $search = trim((string) $request->query->get('q', ''));
        $limit = $request->query->getInt('limit', 50);
        $limit = \in_array($limit, [25, 50, 100], true) ? $limit : 50;
        $page = max(1, $request->query->getInt('page', 1));

        $productNames = $this->syncService->productNames($gn);
        $productCounts = $this->billetSyncRepository->countBy($gn, 'productId');

        return $this->render('gn/billetterie_rapprochement.twig', [
            'gn' => $gn,
            'etat' => $etat,
            'etats' => SyncEtat::cases(),
            'confiance' => $confiance,
            'confiances' => SyncConfiance::cases(),
            'status' => $status,
            'produit' => $productId,
            'q' => $search,
            'paginator' => $this->billetSyncRepository->paginate($gn, $etat, $confiance, $status, $productId, $search, $limit, $page),
            'compteEtats' => $this->billetSyncRepository->countBy($gn, 'etat'),
            'compteConfiances' => $this->billetSyncRepository->countBy($gn, 'confiance', $etat),
            'compteStatuts' => $this->billetSyncRepository->countBy($gn, 'status', $etat),
            'produits' => $productCounts,
            'tarifs' => $this->tarifRows($gn),
            'nomsProduits' => $productNames,
            'billets' => $this->entityManager->getRepository(Billet::class)->findBy(['gn' => $gn], ['label' => 'ASC']),
        ]);
    }

    /**
     * Associe les produits HelloAsso aux billets du GN, puis recalcule les propositions des lignes à valider.
     */
    #[Route('/rapprochement/produits', name: 'produits', methods: ['POST'])]
    public function produitsAction(Request $request, #[MapEntity] Gn $gn): RedirectResponse
    {
        $this->assertEnabled();
        $this->assertCsrf($request, $gn);

        $rows = [];
        foreach ($request->request->all('tarif') as $row) {
            if (!\is_array($row)) {
                continue;
            }
            $price = $row['price'] ?? '';
            $rows[] = [
                'product' => trim((string) ($row['product'] ?? '')),
                'price' => '' === $price || !is_numeric($price) ? null : (int) $price,
                'billet' => (int) ($row['billet'] ?? 0),
                'prioritaire' => '1' === (string) ($row['prioritaire'] ?? ''),
            ];
        }

        try {
            $count = $this->syncService->saveTarifs($gn, $rows);
            $this->addFlash('success', 'Tarifs enregistrés, ' . $count . ' proposition(s) de billet recalculée(s).');
        } catch (InvalidArgumentException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRapprochement($gn);
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
            $admin = $this->getUser();
            $this->syncService->validate($sync, $admin instanceof User ? $admin : null);
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

        $admin = $this->getUser();
        $result = $this->syncService->validateAllSure($gn, $admin instanceof User ? $admin : null);
        $this->addFlash('success', $result['valides'] . ' billet(s) attribué(s).');
        if ($result['doublons'] > 0) {
            $this->addFlash('warning', $result['doublons'] . ' ligne(s) laissée(s) à traiter : le compte a plusieurs lignes payées sans billet prioritaire pour trancher.');
        }

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

    /**
     * Lignes du tableau des tarifs : chaque couple produit + prix présent dans les participants, avec le billet associé.
     *
     * @return list<array{product: string, price: int|null, nb: int, billet: int, prioritaire: bool}>
     */
    private function tarifRows(Gn $gn): array
    {
        $existing = $this->tarifRepository->findByGn($gn);

        $rows = [];
        foreach ($this->billetSyncRepository->countTarifs($gn) as $couple) {
            $tarif = BilletTarifRepository::resolve($existing, $couple['product'], $couple['price']);
            $exact = $tarif instanceof BilletTarif && $tarif->getPrice() === $couple['price'];
            $rows[] = [
                'product' => $couple['product'],
                'price' => $couple['price'],
                'nb' => $couple['nb'],
                'billet' => $exact ? (int) $tarif->getBillet()?->getId() : 0,
                'prioritaire' => $exact && $tarif->isPrioritaire(),
            ];
        }

        return $rows;
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
