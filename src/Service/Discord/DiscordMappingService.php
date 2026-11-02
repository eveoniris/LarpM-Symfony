<?php

declare(strict_types=1);

namespace App\Service\Discord;

use App\Entity\DiscordGuild;
use App\Entity\DiscordRoleMapping;
use App\Entity\SecondaryGroup;
use App\Enum\DiscordRoleType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Enregistre la correspondance rôles LarpManager / rôles Discord d'une guilde saisie depuis la page d'administration.
 */
final readonly class DiscordMappingService
{
    private const string SNOWFLAKE = '/^\d{15,25}$/';

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public static function isSnowflake(string $value): bool
    {
        return 1 === preg_match(self::SNOWFLAKE, $value);
    }

    /**
     * Remplace les rôles de la guilde par ceux saisis : un champ vide supprime la correspondance.
     * Rien n'est enregistré si une saisie est invalide ou si un même rôle Discord est utilisé deux fois.
     *
     * @param array<int|string, string> $groupes identifiant du groupe transverse => ID du rôle Discord
     *
     * @return list<string> erreurs de validation (vide = enregistré)
     */
    public function apply(DiscordGuild $guild, string $membre, string $orga, array $groupes): array
    {
        $desired = [DiscordRoleType::MEMBRE->value => trim($membre), DiscordRoleType::ORGA->value => trim($orga)];
        foreach ($groupes as $groupId => $roleId) {
            $desired['g' . (int) $groupId] = trim($roleId);
        }
        $desired = array_filter($desired, static fn (string $roleId): bool => '' !== $roleId);

        $errors = $this->validate($desired);
        if ([] !== $errors) {
            return $errors;
        }

        $existing = [];
        foreach ($guild->getMappings() as $mapping) {
            $existing[$this->keyOf($mapping)] = $mapping;
        }

        foreach (array_diff_key($existing, $desired) as $mapping) {
            $guild->removeMapping($mapping);
        }

        foreach ($desired as $key => $roleId) {
            if (\array_key_exists($key, $existing)) {
                $existing[$key]->setRoleId($roleId);

                continue;
            }

            $mapping = $this->createMapping($guild, $key);
            if (null !== $mapping) {
                $guild->addMapping($mapping->setRoleId($roleId));
            }
        }

        $this->entityManager->flush();

        return [];
    }

    /**
     * @param array<string, string> $desired
     *
     * @return list<string>
     */
    private function validate(array $desired): array
    {
        $errors = [];
        foreach ($desired as $roleId) {
            if (!self::isSnowflake($roleId)) {
                $errors[] = \sprintf('« %s » n\'est pas un identifiant de rôle Discord valide.', $roleId);
            }
        }
        foreach (array_count_values($desired) as $roleId => $count) {
            if ($count > 1) {
                $errors[] = \sprintf('Le rôle Discord %s est utilisé plusieurs fois.', $roleId);
            }
        }

        return $errors;
    }

    private function createMapping(DiscordGuild $guild, string $key): ?DiscordRoleMapping
    {
        $mapping = (new DiscordRoleMapping())->setGuild($guild);

        if (!str_starts_with($key, 'g')) {
            return $mapping->setType(DiscordRoleType::from($key));
        }

        $group = $this->entityManager->getRepository(SecondaryGroup::class)->find((int) substr($key, offset: 1));

        return null === $group
            ? null
            : $mapping->setType(DiscordRoleType::GROUPE_SECONDAIRE)->setSecondaryGroup($group);
    }

    private function keyOf(DiscordRoleMapping $mapping): string
    {
        return DiscordRoleType::GROUPE_SECONDAIRE === $mapping->getType()
            ? 'g' . $mapping->getSecondaryGroup()?->getId()
            : $mapping->getType()->value;
    }
}
