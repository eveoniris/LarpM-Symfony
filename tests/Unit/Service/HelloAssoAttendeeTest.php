<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\HelloAsso\Attendee;
use App\Service\HelloAsso\FakePlusBilletterieClient;
use App\Service\HelloAsso\PlusBilletterieClient;
use PHPUnit\Framework\TestCase;

class HelloAssoAttendeeTest extends TestCase
{
    public function testFromArrayNormalizeEmailEtNom(): void
    {
        $attendee = Attendee::fromArray([
            'id' => 42,
            'email' => '  Joueur@Example.ORG ',
            'firstName' => 'Jeanne',
            'lastName' => 'Darc',
            'status' => 'enabled',
            'price' => '4500',
            'productId' => 'p1',
        ]);

        $this->assertSame('42', $attendee->id);
        $this->assertSame('joueur@example.org', $attendee->email);
        $this->assertSame('Jeanne Darc', $attendee->nom);
        $this->assertSame(4500, $attendee->price);
        $this->assertNull($attendee->idLarpManager);
    }

    public function testIdLarpManagerDepuisClientReference(): void
    {
        $attendee = Attendee::fromArray(['id' => 'a', 'clientReference' => '1234']);

        $this->assertSame('1234', $attendee->idLarpManager);
    }

    public function testIdLarpManagerDepuisChampPersonnalise(): void
    {
        $attendee = Attendee::fromArray([
            'id' => 'a',
            'customFields' => [
                ['label' => 'Votre ID LarpManager', 'value' => ' 987 '],
                ['label' => 'Allergies', 'value' => 'aucune'],
            ],
        ]);

        $this->assertSame('987', $attendee->idLarpManager);
    }

    public function testClientReferenceNonNumeriqueIgnoree(): void
    {
        $attendee = Attendee::fromArray(['id' => 'a', 'clientReference' => 'abc']);

        $this->assertNull($attendee->idLarpManager);
        $this->assertSame('abc', $attendee->clientReference);
    }

    public function testAttendeesDepuisUnMouvement(): void
    {
        $movement = [
            '_id' => 'mvt1',
            'type' => 'order',
            'status' => 'successful',
            'eventId' => 'evt1',
            'clientReference' => 'acheteur@example.org',
            'clientDetails' => ['firstName' => 'Jeanne', 'lastName' => 'Darc', 'address' => ['city' => 'Rouen']],
            'sellingItems' => [[
                'products' => [
                    ['productId' => 'p1', 'attendeeId' => 'att1', 'attendeeEmail' => 'Joueur@Example.org', 'price' => 10000, 'status' => 'enabled'],
                    ['productId' => 'p2', 'attendeeId' => 'att2', 'attendeeEmail' => 'autre@example.org', 'price' => 5000, 'status' => 'refunded'],
                ],
            ]],
        ];

        $attendees = PlusBilletterieClient::attendeesFromMovement($movement, 'evt1');

        $this->assertCount(2, $attendees);
        $this->assertSame('att1', $attendees[0]->id);
        $this->assertSame('mvt1', $attendees[0]->orderId);
        $this->assertSame('p1', $attendees[0]->productId);
        $this->assertSame('joueur@example.org', $attendees[0]->email);
        $this->assertSame('enabled', $attendees[0]->status);
        $this->assertSame(10000, $attendees[0]->price);
        $this->assertSame('refunded', $attendees[1]->status);
        // L'adresse de l'acheteur n'est pas conservée.
        $this->assertArrayNotHasKey('address', $attendees[0]->raw);
    }

    public function testMouvementAutreEvenementOuNonAbouti(): void
    {
        $base = [
            'type' => 'order',
            'status' => 'successful',
            'eventId' => 'evt1',
            'sellingItems' => [['products' => [['attendeeId' => 'a', 'attendeeEmail' => 'x@example.org']]]],
        ];

        $this->assertSame([], PlusBilletterieClient::attendeesFromMovement($base, 'autre'));
        $this->assertSame([], PlusBilletterieClient::attendeesFromMovement(['status' => 'failed'] + $base, 'evt1'));
        $this->assertSame([], PlusBilletterieClient::attendeesFromMovement(['type' => 'refund'] + $base, 'evt1'));
    }

    public function testFauxClientPagine(): void
    {
        $client = new FakePlusBilletterieClient();

        $page1 = $client->listAttendees('evt', 0, 2);
        $page2 = $client->listAttendees('evt', 2, 2);

        $this->assertCount(2, $page1['items']);
        $this->assertFalse($page1['termine']);
        $this->assertCount(1, $page2['items']);
        $this->assertTrue($page2['termine']);
    }
}
