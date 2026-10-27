<?php

declare(strict_types=1);

namespace App\Service\HelloAsso;

interface PlusBilletterieClientInterface
{
    /**
     * Retourne une page de participants d'un évènement (filtrable sur un produit/tarif).
     *
     * suivant = position à passer au prochain appel ; source = route réellement utilisée (attendees|movements|fake) ;
     * statutAttendees = statut HTTP de la route attendees quand un repli a eu lieu.
     *
     * @return array{items: list<Attendee>, termine: bool, suivant: int, source: string, statutAttendees: int|null}
     *
     * @throws PlusBilletterieException
     */
    public function listAttendees(string $eventId, int $skip = 0, int $limit = 100, ?string $productId = null): array;

    /**
     * Retourne le nom des produits (billets, dons) de l'évènement, indexé par identifiant de produit.
     * Liste vide si la route n'est pas accessible : le nom n'est qu'un confort d'affichage.
     *
     * @return array<string, string>
     */
    public function listProducts(string $eventId): array;

    /**
     * Teste en lecture seule les routes HelloAsso et renvoie le statut HTTP de chacune.
     *
     * @return list<array{route: string, statut: int|null, erreur: string|null}>
     */
    public function diagnose(string $eventId): array;
}
