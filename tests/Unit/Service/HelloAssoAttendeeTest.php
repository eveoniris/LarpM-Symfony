<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\HelloAsso\Attendee;
use App\Service\HelloAsso\FakePlusBilletterieClient;
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
