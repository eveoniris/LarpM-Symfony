<?php

declare(strict_types=1);

namespace App\Service\HelloAsso;

use App\Entity\Billet;
use App\Entity\BilletTarif;
use App\Entity\Gn;
use App\Entity\User;
use App\Enum\SyncConfiance;
use App\Repository\BilletTarifRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Propose, pour un participant HelloAsso, le compte LarpManager et le billet correspondants.
 */
class AttendeeMatcher
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BilletTarifRepository $tarifRepository,
    ) {
    }

    /**
     * @return array{user: ?User, billet: ?Billet, confiance: SyncConfiance}
     */
    /**
     * @param list<BilletTarif>|null $tarifs tarifs du GN déjà chargés (sinon lus ici)
     */
    public function match(Gn $gn, Attendee $attendee, ?string $declaredEmail = null, ?array $tarifs = null): array
    {
        [$user, $confiance] = $this->matchUser($attendee, $declaredEmail);

        return [
            'user' => $user,
            'billet' => $this->matchBillet($gn, $attendee->productId, $attendee->price, $tarifs),
            'confiance' => $confiance,
        ];
    }

    /**
     * @return array{0: ?User, 1: SyncConfiance}
     */
    private function matchUser(Attendee $attendee, ?string $declaredEmail): array
    {
        $repository = $this->entityManager->getRepository(User::class);

        if (null !== $attendee->idLarpManager) {
            $user = $repository->find((int) $attendee->idLarpManager);
            if ($user instanceof User) {
                return [$user, SyncConfiance::SUR];
            }
        }

        // E-mail du compte LarpManager saisi par le joueur dans le formulaire HelloAsso : correspondance déclarée.
        if (null !== $declaredEmail && '' !== $declaredEmail) {
            $users = $repository->findBy(['email' => $declaredEmail]);
            if (1 === \count($users)) {
                return [$users[0], SyncConfiance::SUR];
            }
            if (\count($users) > 1) {
                return [null, SyncConfiance::AMBIGU];
            }
        }

        if (null !== $attendee->email && '' !== $attendee->email) {
            $users = $repository->findBy(['email' => $attendee->email]);
            if (1 === \count($users)) {
                return [$users[0], SyncConfiance::PROBABLE];
            }
            if (\count($users) > 1) {
                return [null, SyncConfiance::AMBIGU];
            }
        }

        return [null, SyncConfiance::AUCUN];
    }

    /**
     * Billet du tarif (produit + prix) associé dans le tableau de rapprochement, sinon billet portant l'identifiant
     * de produit (ancienne association un produit pour un billet).
     *
     * @param list<BilletTarif>|null $tarifs
     */
    public function matchBillet(Gn $gn, ?string $productId, ?int $price, ?array $tarifs = null): ?Billet
    {
        if (null === $productId) {
            return null;
        }

        $tarif = BilletTarifRepository::resolve($tarifs ?? $this->tarifRepository->findByGn($gn), $productId, $price);
        if ($tarif instanceof BilletTarif) {
            return $tarif->getBillet();
        }

        return $this->entityManager
            ->getRepository(Billet::class)
            ->findOneBy([
                'gn' => $gn,
                'helloassoProductId' => $productId,
            ]);
    }
}
