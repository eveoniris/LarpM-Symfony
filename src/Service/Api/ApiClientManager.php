<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Entity\ApiClient;
use App\Entity\Gn;
use App\Entity\User;
use App\Repository\ApiClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use SensitiveParameter;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Création, vérification et révocation des applications tierces de l'API.
 */
final readonly class ApiClientManager
{
    private const string CLIENT_ID_PREFIX = 'lm_';
    private const string ROLE_READ = 'ROLE_API_READ';
    private const string ROLE_ADMIN = 'ROLE_API_ADMIN';

    public function __construct(
        private EntityManagerInterface $em,
        private ApiClientRepository $repository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * Crée l'application et son compte de service. Le secret en clair n'est renvoyé qu'ici, une seule fois.
     *
     * @param list<string> $roles
     *
     * @throws InvalidArgumentException si le nom est déjà pris, ou si ROLE_API_READ est demandé sans GN
     *
     * @return array{client: ApiClient, secret: string}
     */
    public function create(string $name, array $roles, ?Gn $gn = null): array
    {
        if (\in_array(self::ROLE_READ, $roles, true) && !\in_array(self::ROLE_ADMIN, $roles, true) && null === $gn) {
            throw new InvalidArgumentException(\sprintf('Le rôle %s (lecture seule) exige un GN : l\'application est limitée à ce GN.', self::ROLE_READ));
        }

        $username = 'api-' . $name;
        if (null !== $this->em->getRepository(User::class)->findOneBy(['username' => $username])) {
            throw new InvalidArgumentException(\sprintf('Le nom « %s » est déjà pris (compte « %s »), même par une application révoquée. Choisissez un autre nom.', $name, $username));
        }

        $clientId = self::CLIENT_ID_PREFIX . bin2hex(random_bytes(8));
        $secret = bin2hex(random_bytes(32));

        $user = new User(\sprintf('%s@api.larpmanager.invalid', $clientId));
        $user->setUsername($username);
        // Mot de passe aléatoire que personne ne connaît : le compte ne peut pas se connecter par mot de passe.
        $hash = $this->passwordHasher->hashPassword($user, bin2hex(random_bytes(32)));
        $user->setPassword($hash);
        $user->setPwd($hash);
        $user->setRoles($roles);
        $user->setIsEnabled(true);

        $client = new ApiClient($name, $clientId, self::hashSecret($secret), $user);
        $client->setGn($gn);

        $this->em->persist($user);
        $this->em->persist($client);
        $this->em->flush();

        return ['client' => $client, 'secret' => $secret];
    }

    /**
     * Renvoie l'application si l'identifiant et le secret sont valides et l'application active.
     */
    public function authenticate(string $clientId, #[SensitiveParameter] string $secret): ?ApiClient
    {
        $client = $this->repository->findOneByClientId($clientId);

        // Comparaison à durée constante, y compris quand l'identifiant est inconnu.
        $expected = $client?->getSecretHash() ?? str_repeat('0', 64);
        $valid = hash_equals($expected, self::hashSecret($secret));

        if (null === $client || !$valid || !$client->isActive()) {
            return null;
        }

        $client->markUsed();
        $this->em->flush();

        return $client;
    }

    /**
     * Révoque l'application : plus d'échange de jeton, et le compte de service perd tous ses rôles,
     * ce qui coupe aussi immédiatement l'accès aux routes protégées par un rôle avec un jeton déjà émis.
     */
    public function revoke(ApiClient $client): void
    {
        $client->setActive(false);
        $client->getUser()->setRoles([]);
        $this->em->flush();
    }

    private static function hashSecret(#[SensitiveParameter] string $secret): string
    {
        return hash('sha256', $secret);
    }
}
