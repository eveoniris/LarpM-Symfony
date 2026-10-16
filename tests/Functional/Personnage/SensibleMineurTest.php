<?php

declare(strict_types=1);

namespace App\Tests\Functional\Personnage;

use App\Tests\Factory\EtatCivilFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use DateTime;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Un joueur sans date de naissance est traité comme mineur : le champ « sensible »
 * est figé et renvoie vers l'état civil pour corriger la situation.
 */
#[Group('functional')]
class SensibleMineurTest extends WebTestCase
{
    public function testSansDateDeNaissanceLeChampRenvoieVersLEtatCivil(): void
    {
        $client = static::createClient();

        $etatCivil = EtatCivilFactory::createOne(['dateNaissance' => null]);
        $user = UserFactory::createOne(['etatCivil' => $etatCivil]);
        $personnage = PersonnageFactory::createOne(['user' => $user]);

        $client->loginUser($user);
        $crawler = $client->request('GET', '/personnage/' . (string) $personnage->getId() . '/update');

        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('a[href="/etat-civil/' . $etatCivil->getId() . '/update"]'));
        static::assertCount(1, $crawler->filter('select[disabled], input[disabled]'));
    }

    public function testMajeurSansParticipationNEstPasTraiteCommeMineur(): void
    {
        $client = static::createClient();

        $etatCivil = EtatCivilFactory::createOne(['dateNaissance' => new DateTime('-30 years')]);
        $user = UserFactory::createOne(['etatCivil' => $etatCivil]);

        static::assertSame(30, $user->getAgeJoueur());
    }
}
