<?php

declare(strict_types=1);

namespace App\Service\HelloAsso;

/**
 * Participant (billet vendu) renvoyé par l'API HelloAsso Plus Billetterie.
 */
final readonly class Attendee
{
    /**
     * @param array<string, mixed> $raw réponse brute de l'API (conservée pour audit)
     */
    public function __construct(
        public string $id,
        public ?string $orderId,
        public ?string $productId,
        public ?string $email,
        public ?string $nom,
        public ?string $status,
        public ?int $price,
        public ?string $clientReference,
        public ?string $idLarpManager,
        public array $raw = [],
    ) {
    }

    /**
     * Construit un Attendee depuis un élément JSON de l'API. Tolérant : les champs absents restent null.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $nom = trim(\sprintf('%s %s', self::str($data['firstName'] ?? null), self::str($data['lastName'] ?? null)));

        return new self(
            id: self::str($data['_id'] ?? $data['id'] ?? null),
            orderId: self::nullableStr($data['orderId'] ?? null),
            productId: self::nullableStr($data['productId'] ?? null),
            email: isset($data['email']) ? mb_strtolower(trim(self::str($data['email']))) : null,
            nom: '' === $nom ? self::nullableStr($data['name'] ?? null) : $nom,
            status: self::nullableStr($data['status'] ?? null),
            price: isset($data['price']) && is_numeric($data['price']) ? (int) $data['price'] : null,
            clientReference: self::nullableStr($data['clientReference'] ?? null),
            idLarpManager: self::extractLarpManagerId($data),
            raw: $data,
        );
    }

    /**
     * L'ID LarpManager est lu dans la clientReference, sinon dans un champ de formulaire personnalisé
     * dont le libellé contient « larpmanager » (la structure exacte est à confirmer sur les données réelles).
     *
     * @param array<string, mixed> $data
     */
    private static function extractLarpManagerId(array $data): ?string
    {
        $reference = self::nullableStr($data['clientReference'] ?? null);
        if (null !== $reference && ctype_digit($reference)) {
            return $reference;
        }

        foreach (['customFields', 'fields', 'formResponses', 'answers'] as $key) {
            $fields = $data[$key] ?? null;
            if (!\is_array($fields)) {
                continue;
            }

            foreach ($fields as $name => $field) {
                $label = \is_array($field) ? self::str($field['label'] ?? $field['name'] ?? '') : (string) $name;
                $value = \is_array($field) ? $field['value'] ?? $field['answer'] ?? null : $field;
                if (str_contains(mb_strtolower($label), 'larpmanager') && \is_scalar($value) && ctype_digit(trim((string) $value))) {
                    return trim((string) $value);
                }
            }
        }

        return null;
    }

    private static function str(mixed $value): string
    {
        return \is_scalar($value) ? (string) $value : '';
    }

    private static function nullableStr(mixed $value): ?string
    {
        $string = self::str($value);

        return '' === $string ? null : $string;
    }
}
