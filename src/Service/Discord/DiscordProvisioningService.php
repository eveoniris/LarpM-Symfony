<?php

declare(strict_types=1);

namespace App\Service\Discord;

use App\Entity\DiscordGuild;
use App\Entity\DiscordRoleMapping;
use App\Entity\SecondaryGroup;
use App\Enum\DiscordProvisionStatus;
use App\Enum\DiscordRoleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Crée sur une guilde Discord, pour chaque groupe transverse qui n'en a pas : un rôle nommé par un numéro libre,
 * une catégorie privée `NNN-nom-du-groupe` et son unique salon texte du même nom, visibles du seul rôle.
 *
 * Idempotent : un groupe déjà provisionné est ignoré, un provisionnement interrompu est repris là où il s'est arrêté.
 * Ne supprime et ne renomme jamais rien sur Discord.
 */
final readonly class DiscordProvisioningService
{
    private const int CHANNEL_TEXT = 0;
    private const int CHANNEL_CATEGORY = 4;

    private const int PERM_ADD_REACTIONS = 64;
    private const int PERM_MANAGE_CHANNELS = 16;
    private const int PERM_VIEW_CHANNEL = 1024;
    private const int PERM_SEND_MESSAGES = 2048;
    private const int PERM_EMBED_LINKS = 16384;
    private const int PERM_ATTACH_FILES = 32768;
    private const int PERM_READ_HISTORY = 65536;

    /** Limites Discord par guilde. */
    private const int MAX_ROLES = 250;
    private const int MAX_CHANNELS = 500;

    public function __construct(
        private DiscordApiInterface $api,
        private EntityManagerInterface $entityManager,
        /** L'identifiant de l'utilisateur bot est celui de l'application. */
        #[Autowire('%discord.bot.id%')]
        private string $botUserId,
    ) {
    }

    /** Nom du rôle Discord d'un groupe : son numéro sur trois chiffres. */
    public static function roleName(int $numero): string
    {
        return \sprintf('%03d', $numero);
    }

    /** Nom de la catégorie et du salon : `NNN-nom-simplifié` (minuscules, sans accents, 100 caractères maximum). */
    public static function channelName(int $numero, string $label): string
    {
        $slug = (string) (new AsciiSlugger('fr'))
            ->slug($label, '-')
            ->lower();
        $prefix = self::roleName($numero);
        $name = '' === $slug ? $prefix : $prefix . '-' . $slug;

        return rtrim(mb_substr($name, 0, 100), '-');
    }

    /**
     * @param int|null $limit nombre maximal de groupes traités (limite de temps d'une requête web)
     *
     * @return list<DiscordProvisionLine>
     *
     * @throws DiscordApiException si Discord est injoignable avant tout traitement
     */
    public function provision(DiscordGuild $guild, bool $apply, ?int $limit = null): array
    {
        $guildId = $guild->getGuildId();
        $roles = $this->api->guildRoles($guildId);
        $channels = $this->api->guildChannels($guildId);

        $next = max([0, ...$this->usedNumbers($guild, $roles, $channels)]) + 1;
        $targets = $this->targets($guild);
        if (null !== $limit) {
            $targets = \array_slice($targets, 0, $limit);
        }

        $lines = [];
        $rolesCount = \count($roles);
        $channelsCount = \count($channels);

        foreach ($targets as [$group, $mapping]) {
            $numero = $mapping?->getNumero() ?? $next++;
            $roleName = self::roleName($numero);
            $channelName = self::channelName($numero, $group->getLabel());

            if (!$apply) {
                $lines[] = new DiscordProvisionLine($group->getLabel(), $numero, $roleName, $channelName, DiscordProvisionStatus::PLANIFIE);

                continue;
            }

            if (null === $mapping && $rolesCount >= self::MAX_ROLES || ($channelsCount + 2) > self::MAX_CHANNELS) {
                $lines[] = new DiscordProvisionLine(
                    $group->getLabel(),
                    $numero,
                    $roleName,
                    $channelName,
                    DiscordProvisionStatus::ERREUR,
                    'Limite Discord atteinte (250 rôles ou 500 salons par serveur).',
                );

                break;
            }

            try {
                $created = $this->provisionGroup($guild, $group, $mapping, $numero, $roleName, $channelName, $channels);
                $rolesCount += $created['roles'];
                $channelsCount += $created['channels'];
                $lines[] = new DiscordProvisionLine($group->getLabel(), $numero, $roleName, $channelName, DiscordProvisionStatus::CREE);
            } catch (DiscordApiException $exception) {
                $lines[] = new DiscordProvisionLine($group->getLabel(), $numero, $roleName, $channelName, DiscordProvisionStatus::ERREUR, $exception->getMessage());

                // Même cause probable pour les groupes suivants (permissions, limite de débit) : on s'arrête, la relance reprend ici.
                break;
            }
        }

        return $lines;
    }

    /**
     * Groupes à traiter : d'abord les provisionnements interrompus (numéro attribué, catégorie ou salon manquant),
     * puis les groupes sans aucune correspondance sur cette guilde. Une correspondance saisie à la main est laissée telle quelle.
     *
     * @return list<array{0: SecondaryGroup, 1: DiscordRoleMapping|null}>
     */
    private function targets(DiscordGuild $guild): array
    {
        $targets = [];
        $mapped = [];

        foreach ($guild->getMappings() as $mapping) {
            $group = $mapping->getSecondaryGroup();
            if (DiscordRoleType::GROUPE_SECONDAIRE !== $mapping->getType() || null === $group) {
                continue;
            }
            $mapped[$group->getId()] = true;
            if (null !== $mapping->getNumero() && (null === $mapping->getCategoryId() || null === $mapping->getChannelId())) {
                $targets[] = [$group, $mapping];
            }
        }

        foreach ($this->entityManager->getRepository(SecondaryGroup::class)->findBy([], ['id' => 'ASC']) as $group) {
            if (!isset($mapped[$group->getId()])) {
                $targets[] = [$group, null];
            }
        }

        return $targets;
    }

    /**
     * @param list<array{id: string, name: string}>                                     $roles
     * @param list<array{id: string, name: string, type: int, parent_id: string|null}> $channels
     *
     * @return list<int>
     */
    private function usedNumbers(DiscordGuild $guild, array $roles, array $channels): array
    {
        $used = [];
        foreach ($guild->getMappings() as $mapping) {
            if (null !== $mapping->getNumero()) {
                $used[] = $mapping->getNumero();
            }
        }
        foreach ($roles as $role) {
            if (1 === preg_match('/^\d{1,4}$/', $role['name'])) {
                $used[] = (int) $role['name'];
            }
        }
        foreach ($channels as $channel) {
            if (1 === preg_match('/^(\d{1,4})(?:-|$)/', $channel['name'], $matches)) {
                $used[] = (int) $matches[1];
            }
        }

        return $used;
    }

    /**
     * Crée ce qui manque (rôle, catégorie, salon) en enregistrant chaque étape pour qu'une interruption soit reprenable.
     *
     * @param list<array{id: string, name: string, type: int, parent_id: string|null}> $channels salons connus de la guilde, complétés au fil des créations
     *
     * @return array{roles: int, channels: int}
     *
     * @throws DiscordApiException
     */
    private function provisionGroup(DiscordGuild $guild, SecondaryGroup $group, ?DiscordRoleMapping $mapping, int $numero, string $roleName, string $channelName, array &$channels): array
    {
        $guildId = $guild->getGuildId();
        $created = ['roles' => 0, 'channels' => 0];

        if (null === $mapping) {
            $mapping = (new DiscordRoleMapping())
                ->setGuild($guild)
                ->setType(DiscordRoleType::GROUPE_SECONDAIRE)
                ->setSecondaryGroup($group)
                ->setRoleId($this->api->createRole($guildId, $roleName))
                ->setNumero($numero);
            $guild->addMapping($mapping);
            $this->entityManager->flush();
            ++$created['roles'];
        }

        $overwrites = $this->overwrites($guildId, $mapping->getRoleId());

        if (null === $mapping->getCategoryId()) {
            $categoryId = $this->findChannel($channels, $channelName, self::CHANNEL_CATEGORY, null) ?? $this->createChannel(
                $guildId,
                $channelName,
                self::CHANNEL_CATEGORY,
                null,
                $overwrites,
                $channels,
                $created,
            );
            $mapping->setCategoryId($categoryId);
            $this->entityManager->flush();
        }

        if (null === $mapping->getChannelId()) {
            $channelId = $this->findChannel($channels, $channelName, self::CHANNEL_TEXT, $mapping->getCategoryId()) ?? $this->createChannel(
                $guildId,
                $channelName,
                self::CHANNEL_TEXT,
                $mapping->getCategoryId(),
                $overwrites,
                $channels,
                $created,
            );
            $mapping->setChannelId($channelId);
            $this->entityManager->flush();
        }

        return $created;
    }

    /**
     * @param list<array{id: string, type: int, allow: string, deny: string}>          $overwrites
     * @param list<array{id: string, name: string, type: int, parent_id: string|null}> $channels
     * @param array{roles: int, channels: int}                                         $created
     *
     * @throws DiscordApiException
     */
    private function createChannel(string $guildId, string $name, int $type, ?string $parentId, array $overwrites, array &$channels, array &$created): string
    {
        $id = $this->api->createChannel($guildId, $name, $type, $parentId, $overwrites);
        $channels[] = ['id' => $id, 'name' => $name, 'type' => $type, 'parent_id' => $parentId];
        ++$created['channels'];

        return $id;
    }

    /**
     * @param list<array{id: string, name: string, type: int, parent_id: string|null}> $channels
     */
    private function findChannel(array $channels, string $name, int $type, ?string $parentId): ?string
    {
        foreach ($channels as $channel) {
            if ($channel['name'] === $name && $channel['type'] === $type && $channel['parent_id'] === $parentId) {
                return $channel['id'];
            }
        }

        return null;
    }

    /**
     * Invisible pour @everyone (dont l'identifiant est celui de la guilde), accessible au rôle du groupe et au bot lui-même.
     *
     * @return list<array{id: string, type: int, allow: string, deny: string}>
     */
    private function overwrites(string $guildId, string $roleId): array
    {
        $member = self::PERM_VIEW_CHANNEL | self::PERM_SEND_MESSAGES | self::PERM_READ_HISTORY | self::PERM_ADD_REACTIONS | self::PERM_EMBED_LINKS | self::PERM_ATTACH_FILES;
        $bot = self::PERM_VIEW_CHANNEL | self::PERM_SEND_MESSAGES | self::PERM_READ_HISTORY | self::PERM_MANAGE_CHANNELS;

        return [
            ['id' => $guildId, 'type' => 0, 'allow' => '0', 'deny' => (string) self::PERM_VIEW_CHANNEL],
            ['id' => $roleId, 'type' => 0, 'allow' => (string) $member, 'deny' => '0'],
            ['id' => $this->botUserId, 'type' => 1, 'allow' => (string) $bot, 'deny' => '0'],
        ];
    }
}
