<?php

declare(strict_types=1);

namespace App\Service\HelloAsso;

/**
 * Extrait les participants d'un mouvement d'achat de l'API HelloAsso Plus Billetterie (route de repli).
 */
final class MovementAttendees
{
    /**
     * Extrait les billets d'un mouvement d'achat. Seules les données utiles au rapprochement sont conservées
     * (pas d'adresse ni de données bancaires).
     *
     * @param array<array-key, mixed> $movement
     *
     * @return list<Attendee>
     */
    public static function extract(array $movement, string $eventId): array
    {
        if ('order' !== ($movement['type'] ?? null) || 'successful' !== ($movement['status'] ?? null) || ($movement['eventId'] ?? null) !== $eventId) {
            return [];
        }

        $client = \is_array($movement['clientDetails'] ?? null) ? $movement['clientDetails'] : [];
        $attendees = [];

        foreach (\is_array($movement['sellingItems'] ?? null) ? $movement['sellingItems'] : [] as $sellingItem) {
            foreach (\is_array($sellingItem['products'] ?? null) ? $sellingItem['products'] : [] as $product) {
                if (!\is_array($product) || !isset($product['attendeeId'])) {
                    continue;
                }

                $attendees[] = Attendee::fromArray([
                    '_id' => $product['attendeeId'],
                    'orderId' => $movement['_id'] ?? null,
                    'productId' => $product['productId'] ?? null,
                    'email' => $product['attendeeEmail'] ?? null,
                    'firstName' => $client['firstName'] ?? null,
                    'lastName' => $client['lastName'] ?? null,
                    'status' => $product['status'] ?? null,
                    'price' => $product['price'] ?? null,
                    'clientReference' => $movement['clientReference'] ?? null,
                    'formResponseId' => $product['formResponseId'] ?? null,
                    'movementStatus' => $movement['status'] ?? null,
                ]);
            }
        }

        return $attendees;
    }
}
