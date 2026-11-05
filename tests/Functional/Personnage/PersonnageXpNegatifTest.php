<?php

declare(strict_types=1);

namespace App\Tests\Functional\Personnage;

use App\Tests\Factory\EspeceFactory;
use App\Tests\Factory\ExperienceGainFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * L'historique d'xp reste consultable quand le solde du personnage est négatif.
 */
#[Group('functional')]
class PersonnageXpNegatifTest extends WebTestCase
{
    public function testHistoriqueXpAffichePourUnSoldeNegatif(): void
    {
        $client = static::createClient();

        EspeceFactory::createOne(['nom' => 'Humain']);
        $user = UserFactory::createOne();
        $personnage = PersonnageFactory::createOne(['user' => $user, 'xp' => -5]);
        ExperienceGainFactory::createOne(['personnage' => $personnage, 'explanation' => 'gain-historique-xyz']);

        $client->loginUser($user);
        $client->request('GET', '/personnage/' . $personnage->getId());

        static::assertResponseIsSuccessful();
        static::assertStringContainsString('experienceHistory1', (string) $client->getResponse()->getContent());
        static::assertStringContainsString('gain-historique-xyz', (string) $client->getResponse()->getContent());
    }
}
