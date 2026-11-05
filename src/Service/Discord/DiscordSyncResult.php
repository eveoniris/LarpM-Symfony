<?php

declare(strict_types=1);

namespace App\Service\Discord;

/**
 * Résultat de la synchronisation des rôles d'un utilisateur dans une guilde.
 */
final readonly class DiscordSyncResult
{
    /**
     * @param list<string> $added   rôles Discord ajoutés
     * @param list<string> $removed rôles Discord retirés
     */
    public function __construct(
        public string $guildName,
        public bool $inGuild = true,
        public array $added = [],
        public array $removed = [],
        public ?string $error = null,
    ) {
    }

    public function isOk(): bool
    {
        return null === $this->error && $this->inGuild;
    }

    public function summary(): string
    {
        if (null !== $this->error) {
            return \sprintf('%s : %s', $this->guildName, $this->error);
        }
        if (!$this->inGuild) {
            return \sprintf('%s : vous n\'êtes pas encore sur ce serveur Discord, rejoignez-le puis relancez la synchronisation.', $this->guildName);
        }
        if ([] === $this->added && [] === $this->removed) {
            return \sprintf('%s : vos rôles sont déjà à jour.', $this->guildName);
        }

        return \sprintf('%s : %d rôle(s) ajouté(s), %d retiré(s).', $this->guildName, \count($this->added), \count($this->removed));
    }
}
