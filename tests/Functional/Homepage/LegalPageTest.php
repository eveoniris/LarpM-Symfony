<?php

declare(strict_types=1);

namespace App\Tests\Functional\Homepage;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * La page des mentions légales décrit le journal de connexion, avec des durées alignées sur la commande de purge.
 */
#[Group('functional')]
class LegalPageTest extends WebTestCase
{
    public function testLaPageEstPubliqueEtDecritLeJournalDeConnexion(): void
    {
        $client = static::createClient();

        $client->request('GET', '/legal');

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('#journal-connexion');
        static::assertSelectorTextContains('body', 'anonymisée au bout de 30 jours');
        static::assertSelectorTextContains('body', 'supprimée au bout de 12 mois');
    }
}
