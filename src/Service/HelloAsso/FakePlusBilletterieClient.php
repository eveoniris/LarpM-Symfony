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

    public function listAttendees(string $eventId, int $skip = 0, int $limit = 100): array
    {
        $all = $this->attendees ?? self::defaultAttendees();
        $items = \array_slice($all, $skip, $limit);

        return [
            'items' => $items,
            'termine' => ($skip + \count($items)) >= \count($all),
            'suivant' => $skip + \count($items),
        ];
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
