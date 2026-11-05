<?php

declare(strict_types=1);

namespace App\Service\Discord;

use App\Entity\DiscordGuild;
use App\Entity\User;
use App\Enum\DiscordRoleType;
use App\Repository\DiscordGuildRepository;

/**
 * Aligne les rôles Discord d'un utilisateur lié sur ce que LarpManager calcule (ajouts et retraits).
 */
final readonly class DiscordSyncService
{
    public function __construct(
        private DiscordRoleResolver $resolver,
        private DiscordApiInterface $api,
        private DiscordGuildRepository $guilds,
    ) {
    }

    /**
     * Synchronise l'utilisateur dans toutes les guildes configurées.
     *
     * @return list<DiscordSyncResult>
     */
    public function syncAll(User $user): array
    {
        $results = [];
        foreach ($this->guilds->findAllOrdered() as $guild) {
            $results[] = $this->sync($user, $guild);
        }

        return $results;
    }

    /**
     * @param bool $apply faux : calcule les ajouts et retraits sans rien modifier sur Discord (simulation)
     */
    public function sync(User $user, DiscordGuild $guild, bool $apply = true): DiscordSyncResult
    {
        $discordId = $user->getDiscordId();
        if (null === $discordId) {
            return new DiscordSyncResult($guild->getNom(), error: 'compte Discord non lié.');
        }

        try {
            $current = $this->api->memberRoleIds($guild->getGuildId(), $discordId);
            if (null === $current) {
                return new DiscordSyncResult($guild->getNom(), inGuild: false);
            }

            $desired = $this->resolver->desiredRoleIds($user, $guild);
            $managed = $this->resolver->managedRoleIds($guild);

            $toAdd = array_values(array_diff($desired, $current));
            $toRemove = array_values(array_diff(array_intersect($managed, $current), $desired));

            if ($apply) {
                foreach ($toAdd as $roleId) {
                    $this->api->addRole($guild->getGuildId(), $discordId, $roleId);
                }
                foreach ($toRemove as $roleId) {
                    $this->api->removeRole($guild->getGuildId(), $discordId, $roleId);
                }
            }
        } catch (DiscordApiException $exception) {
            return new DiscordSyncResult($guild->getNom(), error: $exception->getMessage());
        }

        return new DiscordSyncResult($guild->getNom(), added: $toAdd, removed: $toRemove, addedLabels: $this->labelsOf($guild, $toAdd), removedLabels: $this->labelsOf($guild, $toRemove));
    }

    /**
     * Noms lisibles des rôles Discord d'après la correspondance de la guilde (Membre, Orga / scénariste, nom du groupe).
     *
     * @param list<string> $roleIds
     *
     * @return list<string>
     */
    private function labelsOf(DiscordGuild $guild, array $roleIds): array
    {
        $labels = [];
        foreach ($guild->getMappings() as $mapping) {
            $labels[$mapping->getRoleId()] = match ($mapping->getType()) {
                DiscordRoleType::MEMBRE => 'Membre',
                DiscordRoleType::ORGA => 'Orga / scénariste',
                DiscordRoleType::GROUPE_SECONDAIRE => $mapping->getSecondaryGroup()?->getLabel() ?? 'Groupe',
            };
        }

        return array_map(static fn (string $roleId): string => $labels[$roleId] ?? 'Rôle ' . $roleId, $roleIds);
    }
}
