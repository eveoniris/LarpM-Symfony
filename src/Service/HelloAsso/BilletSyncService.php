<?php

declare(strict_types=1);

namespace App\Service\HelloAsso;

use App\Entity\Billet;
use App\Entity\BilletSync;
use App\Entity\BilletTarif;
use App\Entity\Gn;
use App\Entity\LogAction;
use App\Entity\Participant;
use App\Entity\User;
use App\Enum\LogActionType;
use App\Enum\SyncConfiance;
use App\Enum\SyncEtat;
use App\Repository\BilletSyncRepository;
use App\Repository\BilletTarifRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Synchronise les participants HelloAsso Plus Billetterie d'un GN (par lots) et applique les rapprochements validés.
 */
class BilletSyncService
{
    public const int BATCH_SIZE = 100;

    /** @var array<int, list<BilletTarif>> */
    private array $tarifsParGn = [];

    public function __construct(
        private readonly PlusBilletterieClientInterface $client,
        private readonly AttendeeMatcher $matcher,
        private readonly EntityManagerInterface $entityManager,
        private readonly BilletSyncRepository $billetSyncRepository,
        private readonly BilletTarifRepository $tarifRepository,
        private readonly CacheInterface $cache,
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
        $declared = $this->declaredEmails($eventId, 0 === $skip);

        $tarifs = $this->tarifRepository->findByGn($gn);

        foreach ($page['items'] as $attendee) {
            $this->upsert($gn, $attendee, $declared, $tarifs);
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
        // Une ligne prioritaire (ex. don place solidaire) l'emporte sur les autres lignes payées du même compte.
        $prioritaire = $this->billetPrioritaire($sync, $user, $gn);
        $couvertPar = null;
        if (null !== $prioritaire && $prioritaire['billet'] !== $billet && !$this->estPrioritaire($sync)) {
            $billet = $prioritaire['billet'];
            $couvertPar = $prioritaire['attendeeId'];
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
            'couvert_par' => $couvertPar,
        ]);
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    /**
     * Valide les lignes sûres (compte déclaré) ou probables (e-mail unique), avec billet connu et payées.
     * Les lignes ambiguës ou sans correspondance ne sont jamais validées automatiquement. Un compte qui a plusieurs
     * lignes payées n'est validé que si un seul billet prioritaire (ex. place solidaire) tranche ; sinon ses lignes
     * sont comptées comme doublons et laissées à traiter à la main.
     *
     * @return array{valides: int, doublons: int}
     */
    public function validateAllSure(Gn $gn, ?User $admin): array
    {
        $groups = [];
        foreach ($this->billetSyncRepository->findBy(['gn' => $gn]) as $sync) {
            $user = $sync instanceof BilletSync ? $sync->getUser() : null;
            if (!$sync instanceof BilletSync || null === $user || !$sync->isPaye() || SyncEtat::IGNORE === $sync->getEtat()) {
                continue;
            }
            $groups[(int) $user->getId()][] = $sync;
        }

        $valides = 0;
        $doublons = 0;
        foreach ($groups as $lines) {
            $pending = array_filter($lines, fn (BilletSync $sync): bool => SyncEtat::A_VALIDER === $sync->getEtat() && $this->estValidableAutomatiquement($sync));
            if ([] === $pending) {
                continue;
            }
            if (\count($lines) > 1 && !$this->untrancheParPrioritaire($lines)) {
                $doublons += \count($pending);
                continue;
            }

            foreach ($pending as $sync) {
                $this->validate($sync, $admin);
                ++$valides;
            }
        }

        return ['valides' => $valides, 'doublons' => $doublons];
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
     * Enregistre les correspondances produit + prix -> billet du GN (elles remplacent les précédentes), puis recalcule
     * le billet proposé des lignes encore « à valider ». Les lignes déjà rapprochées ou ignorées ne bougent pas.
     *
     * @param list<array{product: string, price: int|null, billet: int, prioritaire: bool}> $rows
     *
     * @return int nombre de lignes dont le billet proposé a été recalculé
     *
     * @throws InvalidArgumentException si un billet n'appartient pas au GN ou si un couple produit + prix est en double
     */
    public function saveTarifs(Gn $gn, array $rows): int
    {
        $billets = [];
        foreach ($gn->getBillets() as $billet) {
            $billets[(int) $billet->getId()] = $billet;
        }

        $nouveaux = [];
        foreach ($rows as $row) {
            if ($row['billet'] <= 0 || '' === $row['product']) {
                continue;
            }
            if (!isset($billets[$row['billet']])) {
                throw new InvalidArgumentException('Billet introuvable pour ce GN.');
            }
            $key = $row['product'] . '|' . ($row['price'] ?? 'tous');
            if (isset($nouveaux[$key])) {
                throw new InvalidArgumentException('Le produit ' . $row['product'] . ' est associé deux fois au même prix.');
            }
            $nouveaux[$key] = (new BilletTarif())
                ->setGn($gn)
                ->setProductId($row['product'])
                ->setPrice($row['price'])
                ->setBillet($billets[$row['billet']])
                ->setPrioritaire($row['prioritaire']);
        }

        foreach ($this->tarifRepository->findByGn($gn) as $ancien) {
            $this->entityManager->remove($ancien);
        }
        // L'ancienne association « un produit par billet » est remplacée par ces tarifs.
        foreach ($billets as $billet) {
            $billet->setHelloassoProductId(null);
        }
        $this->entityManager->flush();

        foreach ($nouveaux as $tarif) {
            $this->entityManager->persist($tarif);
        }
        $this->entityManager->flush();
        unset($this->tarifsParGn[(int) $gn->getId()]);

        $tarifs = array_values($nouveaux);
        $count = 0;
        foreach ($this->billetSyncRepository->findByGnAndEtat($gn, SyncEtat::A_VALIDER) as $sync) {
            $billet = $this->matcher->matchBillet($gn, $sync->getProductId(), $sync->getPrice(), $tarifs);
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

    /**
     * E-mails de compte LarpManager déclarés dans les formulaires, mis en cache le temps d'une synchronisation
     * (une lecture complète des réponses serait refaite à chaque lot de 100 sinon).
     *
     * @return array<string, string>
     */
    private function declaredEmails(string $eventId, bool $refresh): array
    {
        $key = 'helloasso_declared_emails_' . md5($eventId);
        if ($refresh) {
            $this->cache->delete($key);
        }

        return $this->cache->get($key, function (ItemInterface $item) use ($eventId): array {
            $item->expiresAfter(900);

            return $this->client->declaredEmails($eventId);
        });
    }

    /**
     * @param array<string, string> $declared
     * @param list<BilletTarif>     $tarifs
     */
    private function upsert(Gn $gn, Attendee $attendee, array $declared, array $tarifs): void
    {
        if ('' === $attendee->id) {
            return;
        }

        $sync = $this->billetSyncRepository->findOneByAttendee($gn, $attendee->id);
        $nouveau = null === $sync;
        $sync ??= $this->creer($gn, $attendee->id);

        $declaredEmail = $declared[$attendee->formResponseId() ?? ''] ?? null;
        $this->remplir($sync, $attendee, $declaredEmail);

        // Une ligne déjà traitée garde sa décision ; sinon on (re)calcule la proposition.
        if ($nouveau || SyncEtat::A_VALIDER === $sync->getEtat()) {
            $match = $this->matcher->match($gn, $attendee, $declaredEmail, $tarifs);
            $sync->setUser($match['user']);
            $sync->setBillet($match['billet']);
            $sync->setConfiance($match['confiance']);
        }
    }

    /** @return list<BilletTarif> */
    private function tarifs(Gn $gn): array
    {
        return $this->tarifsParGn[(int) $gn->getId()] ??= $this->tarifRepository->findByGn($gn);
    }

    private function tarifDe(BilletSync $sync): ?BilletTarif
    {
        $gn = $sync->getGn();

        return null === $gn ? null : BilletTarifRepository::resolve($this->tarifs($gn), $sync->getProductId(), $sync->getPrice());
    }

    private function estPrioritaire(BilletSync $sync): bool
    {
        return $this->tarifDe($sync)?->isPrioritaire() ?? false;
    }

    /**
     * Billet prioritaire d'une autre ligne payée du même compte (ex. don place solidaire), s'il existe.
     *
     * @return array{billet: Billet, attendeeId: string}|null
     */
    private function billetPrioritaire(BilletSync $sync, User $user, Gn $gn): ?array
    {
        foreach ($this->billetSyncRepository->findBy(['gn' => $gn, 'user' => $user]) as $autre) {
            if (!$autre instanceof BilletSync || $autre === $sync || SyncEtat::IGNORE === $autre->getEtat() || !$autre->isPaye()) {
                continue;
            }
            $tarif = $this->tarifDe($autre);
            if ($tarif?->isPrioritaire() && null !== $tarif->getBillet()) {
                return ['billet' => $tarif->getBillet(), 'attendeeId' => $autre->getAttendeeId()];
            }
        }

        return null;
    }

    /**
     * Vrai si, parmi les lignes payées d'un compte, un seul billet prioritaire existe : il tranche.
     *
     * @param list<BilletSync> $lines
     */
    private function untrancheParPrioritaire(array $lines): bool
    {
        $billets = [];
        foreach ($lines as $line) {
            $tarif = $this->tarifDe($line);
            if ($tarif?->isPrioritaire() && null !== $tarif->getBillet()) {
                $billets[(int) $tarif->getBillet()->getId()] = true;
            }
        }

        return 1 === \count($billets);
    }

    private function creer(Gn $gn, string $attendeeId): BilletSync
    {
        $sync = new BilletSync();
        $sync->setGn($gn);
        $sync->setAttendeeId($attendeeId);
        $this->entityManager->persist($sync);

        return $sync;
    }

    private function remplir(BilletSync $sync, Attendee $attendee, ?string $declaredEmail): void
    {
        $sync->setOrderId($attendee->orderId);
        $sync->setProductId($attendee->productId);
        $sync->setEmail($attendee->email);
        $sync->setNom($attendee->nom);
        $sync->setStatus($attendee->status);
        $sync->setPrice($attendee->price);
        $sync->setRawData(null === $declaredEmail ? $attendee->raw : [...$attendee->raw, '_emailLarpManager' => $declaredEmail]);
        $sync->setSynchroniseLe(new DateTime());
    }

    private function estValidableAutomatiquement(BilletSync $sync): bool
    {
        return \in_array($sync->getConfiance(), [SyncConfiance::SUR, SyncConfiance::PROBABLE], strict: true) && null !== $sync->getUser() && null !== $sync->getBillet() && $sync->isPaye();
    }
}
