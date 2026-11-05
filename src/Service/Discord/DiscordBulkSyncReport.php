<?php

declare(strict_types=1);

namespace App\Service\Discord;

/**
 * Bilan de la synchronisation de tous les comptes liés dans une guilde.
 */
final class DiscordBulkSyncReport
{
    /** Utilisateurs traités. */
    public int $processed = 0;

    /** Utilisateurs dont les rôles étaient déjà à jour. */
    public int $upToDate = 0;

    /** Utilisateurs liés mais absents du serveur Discord. */
    public int $absent = 0;

    public int $errors = 0;

    /** Nombre de comptes liés au total. */
    public int $total = 0;

    /** Décalage de la page suivante, ou null si tout a été traité. */
    public ?int $nextOffset = null;

    /** Arrêt anticipé (Discord injoignable ou limite de débit persistante). */
    public bool $aborted = false;

    /** @var list<array{user: string, added: list<string>, removed: list<string>, error: string|null}> noms des rôles ajoutés et retirés par joueur */
    public array $changes = [];
}
