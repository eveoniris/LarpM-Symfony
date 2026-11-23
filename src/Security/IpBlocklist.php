<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\IpBloquee;
use App\Entity\User;
use App\Repository\IpBloqueeRepository;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Liste des IP bloquées par un administrateur (mise en cache, invalidée à chaque écriture).
 */
// @mago-expect lint:cyclomatic-complexity
class IpBlocklist
{
    public const string CACHE_KEY = 'ip_blocklist_active';
    public const int CACHE_TTL = 300;
    public const int DEFAULT_DAYS = 7;
    public const int MAX_DAYS = 365;

    public function __construct(
        private readonly IpBloqueeRepository $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    public function isBlocked(?string $ip): bool
    {
        if (null === $ip || '' === $ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        $cidrs = $this->activeCidrs();

        return [] !== $cidrs && IpUtils::checkIp($ip, $cidrs);
    }

    /**
     * Bloque une IP (IPv6 : son /64) ou une plage CIDR. Un blocage déjà présent est prolongé.
     *
     * @throws InvalidArgumentException adresse invalide, privée, ou celle de l'administrateur
     */
    public function block(
        string $ipOrCidr,
        ?User $by = null,
        ?string $motif = null,
        int $days = self::DEFAULT_DAYS,
        ?string $callerIp = null,
    ): IpBloquee {
        $cidr = $this->normalize($ipOrCidr);

        if (null === $cidr) {
            throw new InvalidArgumentException('Adresse IP ou plage invalide.');
        }

        $base = explode('/', $cidr)[0];

        if (IpUtils::isPrivateIp($base) || \in_array($base, ['127.0.0.1', '::1', '0.0.0.0', '::'], true)) {
            throw new InvalidArgumentException('Une adresse privée ou locale ne peut pas être bloquée.');
        }

        if (null !== $callerIp && IpUtils::checkIp($callerIp, $cidr)) {
            throw new InvalidArgumentException('Vous ne pouvez pas bloquer votre propre adresse.');
        }

        $days = max(1, min($days, self::MAX_DAYS));
        $expires = (new DateTimeImmutable())->add(new DateInterval('P' . $days . 'D'));

        $entry = $this->repository->findOneBy(['cidr' => $cidr]) ?? new IpBloquee($cidr, $expires);
        $entry->setExpiresAt($expires)->setMotif($motif)->setCreatedBy($by);

        $this->entityManager->persist($entry);
        $this->entityManager->flush();
        $this->invalidate();

        return $entry;
    }

    public function unblock(IpBloquee $entry): void
    {
        $this->entityManager->remove($entry);
        $this->entityManager->flush();
        $this->invalidate();
    }

    public function invalidate(): void
    {
        $this->cache->deleteItem(self::CACHE_KEY);
    }

    /** « 1.2.3.4 » -> « 1.2.3.4/32 », IPv6 seule -> son /64, CIDR valide conservé, sinon null. */
    public function normalize(string $ipOrCidr): ?string
    {
        $ipOrCidr = trim($ipOrCidr);

        if (str_contains($ipOrCidr, '/')) {
            [$ip, $prefix] = explode('/', $ipOrCidr, 2);
            $isV6 = str_contains($ip, ':');

            if (!filter_var($ip, FILTER_VALIDATE_IP) || !ctype_digit($prefix) || (int) $prefix > ($isV6 ? 128 : 32)) {
                return null;
            }

            return $ip . '/' . (int) $prefix;
        }

        if (!filter_var($ipOrCidr, FILTER_VALIDATE_IP)) {
            return null;
        }

        if (str_contains($ipOrCidr, ':')) {
            $packed = inet_pton($ipOrCidr);

            if (false === $packed) {
                return null;
            }

            $network = inet_ntop(substr($packed, 0, 8) . str_repeat("\0", 8));

            return false === $network ? null : $network . '/64';
        }

        return $ipOrCidr . '/32';
    }

    /** @return list<string> */
    private function activeCidrs(): array
    {
        $item = $this->cache->getItem(self::CACHE_KEY);

        if ($item->isHit()) {
            /** @var list<string> */
            return $item->get();
        }

        $cidrs = $this->repository->findActiveCidrs();
        $item->set($cidrs)->expiresAfter(self::CACHE_TTL);
        $this->cache->save($item);

        return $cidrs;
    }
}
