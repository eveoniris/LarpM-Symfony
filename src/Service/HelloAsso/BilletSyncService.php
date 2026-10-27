<?php

declare(strict_types=1);

namespace App\Service\HelloAsso;

use App\Entity\BilletSync;
use App\Entity\Gn;
use App\Entity\LogAction;
use App\Entity\Participant;
use App\Entity\User;
use App\Enum\LogActionType;
use App\Enum\SyncConfiance;
use App\Enum\SyncEtat;
use App\Repository\BilletSyncRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

/**
 * Synchronise les participants HelloAsso Plus Billetterie d'un GN (par lots) et applique les rapprochements validés.
 */
class BilletSyncService
{
    public const int BATCH_SIZE = 100;

    public function __construct(
        private readonly PlusBilletterieClientInterface $client,
        private readonly AttendeeMatcher $matcher,
        private readonly EntityManagerInterface $entityManager,
        private readonly BilletSyncRepository $billetSyncRepository,
    ) {
    }

    /**
     * Lit un lot de participants et met à jour la table de rapprochement (upsert sur l'identifiant HelloAsso).
     * Les lignes déjà validées ou ignorées ne sont pas repassées « à valider ».
     *
     * @return array{traites: int, termine: bool, suivant: int, source: string, statutAttendees: int|null}
     *
     * @throws PlusBilletterieException
     * @throws InvalidArgumentException si le GN n'a pas d'évènement HelloAsso
     */
    public function syncBatch(Gn $gn, int $skip = 0, int $limit = self::BATCH_SIZE): array
    {
        $eventId = $gn->getHelloassoEventId();
        if (null === $eventId) {
            throw new InvalidArgumentException("Ce GN n'a pas d'identifiant d'évènement HelloAsso.");
        }

        $page = $this->client->listAttendees($eventId, $skip, $limit);

        foreach ($page['items'] as $attendee) {
            $this->upsert($gn, $attendee);
        }

        $this->entityManager->flush();

        return [
            'traites' => \count($page['items']),
            'termine' => $page['termine'],
            'suivant' => $page['suivant'],
            'source' => $page['source'],
            'statutAttendees' => $page['statutAttendees'],
        ];
    }

    /**
     * Teste en lecture seule les routes HelloAsso pour le GN (diagnostic super admin).
     *
     * @return list<array{route: string, statut: int|null, erreur: string|null}>
     *
     * @throws InvalidArgumentException si le GN n'a pas d'évènement HelloAsso
     */
    public function diagnose(Gn $gn): array
    {
        $eventId = $gn->getHelloassoEventId();
        if (null === $eventId) {
            throw new InvalidArgumentException("Ce GN n'a pas d'identifiant d'évènement HelloAsso.");
        }

        return $this->client->diagnose($eventId);
    }

    /**
     * Applique une ligne : crée le participant du GN si besoin et lui attache le billet.
     * Idempotent : une ligne déjà rapprochée n'est pas rejouée.
     *
     * @throws InvalidArgumentException si la ligne n'est pas validable
     */
    public function validate(BilletSync $sync, ?User $admin): void
    {
        if (SyncEtat::RAPPROCHE === $sync->getEtat()) {
            return;
        }

        $user = $sync->getUser();
        $billet = $sync->getBillet();
        $gn = $sync->getGn();
        if (null === $user || null === $billet || null === $gn) {
            throw new InvalidArgumentException('Utilisateur ou billet manquant pour ' . ($sync->getEmail() ?? $sync->getAttendeeId()) . '.');
        }
        if (!$sync->isPaye()) {
            throw new InvalidArgumentException('Le billet de ' . ($sync->getEmail() ?? $sync->getAttendeeId()) . " n'est pas payé (statut " . $sync->getStatus() . ').');
        }

        $participant = $user->getParticipant($gn);
        if (null === $participant) {
            $participant = new Participant();
            $participant->setUser($user);
            $participant->setGn($gn);
            $this->entityManager->persist($participant);
        }
        $participant->setBillet($billet);

        $sync->setEtat(SyncEtat::RAPPROCHE);
        $sync->setValideLe(new DateTime());

        $log = new LogAction();
        $log->setDate(new DateTime());
        $log->setType(LogActionType::BILLETTERIE_SYNC);
        $log->setUser($admin);
        $log->setData([
            'attendee_id' => $sync->getAttendeeId(),
            'user_id' => $user->getId(),
            'billet_id' => $billet->getId(),
            'gn_id' => $gn->getId(),
        ]);
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    /**
     * Valide toutes les lignes sûres (ID LarpManager) ou probables à e-mail unique, avec billet connu et payées.
     * Les lignes ambiguës ou sans correspondance ne sont jamais validées automatiquement.
     *
     * @return int nombre de lignes validées
     */
    public function validateAllSure(Gn $gn, ?User $admin): int
    {
        $count = 0;
        foreach ($this->billetSyncRepository->findByGnAndEtat($gn, SyncEtat::A_VALIDER) as $sync) {
            if (!$this->estValidableAutomatiquement($sync)) {
                continue;
            }

            $this->validate($sync, $admin);
            ++$count;
        }

        return $count;
    }

    /**
     * Nom des produits HelloAsso de l'évènement du GN (vide si la route n'est pas accessible).
     *
     * @return array<string, string>
     */
    public function productNames(Gn $gn): array
    {
        $eventId = $gn->getHelloassoEventId();

        return null === $eventId ? [] : $this->client->listProducts($eventId);
    }

    /**
     * Associe des produits HelloAsso à des billets du GN (un produit par billet), puis recalcule les propositions
     * des lignes encore « à valider ». Les lignes déjà rapprochées ou ignorées ne bougent pas.
     *
     * @param array<string, int> $billetByProduct identifiant de produit => identifiant de billet (0 pour dissocier)
     *
     * @return int nombre de lignes dont le billet proposé a été recalculé
     *
     * @throws InvalidArgumentException si un billet est choisi pour plusieurs produits ou n'appartient pas au GN
     */
    public function associateProducts(Gn $gn, array $billetByProduct): int
    {
        $billets = [];
        foreach ($gn->getBillets() as $billet) {
            $billets[(int) $billet->getId()] = $billet;
        }

        $chosen = array_filter($billetByProduct, static fn (int $id): bool => $id > 0);
        if (\count($chosen) !== \count(array_unique($chosen))) {
            throw new InvalidArgumentException('Un billet ne peut être associé qu\'à un seul produit HelloAsso.');
        }
        foreach ($chosen as $billetId) {
            if (!isset($billets[$billetId])) {
                throw new InvalidArgumentException('Billet introuvable pour ce GN.');
            }
        }

        // On libère d'abord les produits concernés, puis on pose les nouveaux liens.
        foreach ($billets as $billet) {
            if (\array_key_exists((string) $billet->getHelloassoProductId(), $billetByProduct)) {
                $billet->setHelloassoProductId(null);
            }
        }
        $this->entityManager->flush();
        foreach ($chosen as $productId => $billetId) {
            $billets[$billetId]->setHelloassoProductId((string) $productId);
        }
        $this->entityManager->flush();

        $count = 0;
        foreach ($this->billetSyncRepository->findByGnAndEtat($gn, SyncEtat::A_VALIDER) as $sync) {
            $billet = null;
            foreach ($billets as $candidate) {
                if (null !== $sync->getProductId() && $candidate->getHelloassoProductId() === $sync->getProductId()) {
                    $billet = $candidate;
                    break;
                }
            }
            if ($sync->getBillet() !== $billet) {
                $sync->setBillet($billet);
                ++$count;
            }
        }
        $this->entityManager->flush();

        return $count;
    }

    public function ignore(BilletSync $sync): void
    {
        $sync->setEtat(SyncEtat::IGNORE);
        $this->entityManager->flush();
    }

    private function upsert(Gn $gn, Attendee $attendee): void
    {
        if ('' === $attendee->id) {
            return;
        }

        $sync = $this->billetSyncRepository->findOneByAttendee($gn, $attendee->id);
        $nouveau = null === $sync;
        $sync ??= $this->creer($gn, $attendee->id);

        $this->remplir($sync, $attendee);

        // Une ligne déjà traitée garde sa décision ; sinon on (re)calcule la proposition.
        if ($nouveau || SyncEtat::A_VALIDER === $sync->getEtat()) {
            $match = $this->matcher->match($gn, $attendee);
            $sync->setUser($match['user']);
            $sync->setBillet($match['billet']);
            $sync->setConfiance($match['confiance']);
        }
    }

    private function creer(Gn $gn, string $attendeeId): BilletSync
    {
        $sync = new BilletSync();
        $sync->setGn($gn);
        $sync->setAttendeeId($attendeeId);
        $this->entityManager->persist($sync);

        return $sync;
    }

    private function remplir(BilletSync $sync, Attendee $attendee): void
    {
        $sync->setOrderId($attendee->orderId);
        $sync->setProductId($attendee->productId);
        $sync->setEmail($attendee->email);
        $sync->setNom($attendee->nom);
        $sync->setStatus($attendee->status);
        $sync->setPrice($attendee->price);
        $sync->setRawData($attendee->raw);
        $sync->setSynchroniseLe(new DateTime());
    }

    private function estValidableAutomatiquement(BilletSync $sync): bool
    {
        return \in_array($sync->getConfiance(), [SyncConfiance::SUR, SyncConfiance::PROBABLE], strict: true) && null !== $sync->getUser() && null !== $sync->getBillet() && $sync->isPaye();
    }
}
