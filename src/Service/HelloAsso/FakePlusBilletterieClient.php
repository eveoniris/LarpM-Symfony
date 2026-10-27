<?php

declare(strict_types=1);

namespace App\Service\HelloAsso;

/**
 * Faux client utilisé quand aucune clé API n'est configurée (développement, tests).
 */
class FakePlusBilletterieClient implements PlusBilletterieClientInterface
{
    /** @var list<Attendee>|null */
    private ?array $attendees = null;

    /** @param list<Attendee>|null $attendees jeu de données personnalisé (tests) */
    public function __construct(?array $attendees = null)
    {
        $this->attendees = $attendees;
    }

    public function listAttendees(string $eventId, int $skip = 0, int $limit = 100, ?string $productId = null): array
    {
        $all = $this->attendees ?? self::defaultAttendees();
        if (null !== $productId) {
            $all = array_values(array_filter($all, static fn (Attendee $attendee): bool => $attendee->productId === $productId));
        }
        $items = \array_slice($all, $skip, $limit);

        return [
            'items' => $items,
            'termine' => ($skip + \count($items)) >= \count($all),
            'suivant' => $skip + \count($items),
            'source' => 'fake',
            'statutAttendees' => null,
        ];
    }

    public function listProducts(string $eventId): array
    {
        $names = [];
        foreach ($this->attendees ?? self::defaultAttendees() as $attendee) {
            if (null !== $attendee->productId) {
                $names[$attendee->productId] = 'Produit ' . $attendee->productId;
            }
        }

        return $names;
    }

    public function diagnose(string $eventId): array
    {
        return [['route' => '/events/{eventId}/attendees', 'statut' => null, 'erreur' => 'Faux client : aucune clé API configurée.']];
    }

    /** @return list<Attendee> */
    private static function defaultAttendees(): array
    {
        return [
            Attendee::fromArray([
                'id' => 'fake-1',
                'email' => 'test@test.com',
                'firstName' => 'Test',
                'lastName' => 'Admin',
                'status' => 'enabled',
                'price' => 4500,
                'productId' => 'fake-product-pj',
                'orderId' => 'fake-order-1',
            ]),
            Attendee::fromArray([
                'id' => 'fake-2',
                'email' => 'inconnu@example.org',
                'firstName' => 'Inconnu',
                'lastName' => 'Joueur',
                'status' => 'enabled',
                'price' => 4500,
                'productId' => 'fake-product-pj',
                'orderId' => 'fake-order-2',
            ]),
            Attendee::fromArray([
                'id' => 'fake-3',
                'email' => 'rembourse@example.org',
                'firstName' => 'Rembourse',
                'lastName' => 'Joueur',
                'status' => 'refunded',
                'price' => 4500,
                'productId' => 'fake-product-pj',
                'orderId' => 'fake-order-3',
            ]),
        ];
    }
}
