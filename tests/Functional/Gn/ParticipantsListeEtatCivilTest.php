<?php

declare(strict_types=1);

namespace App\Tests\Functional\Gn;

use App\Tests\Factory\EtatCivilFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Régression : ParticipantRepository::search() joignait user.etatCivil en INNER
 * JOIN, ce qui faisait disparaître de la liste des participants tout joueur
 * n'ayant pas rempli ses informations administratives — précisément ceux que
 * l'organisation doit relancer.
 *
 * DAMA enveloppe chaque test dans une transaction annulée automatiquement.
 */
#[Group('functional')]
class ParticipantsListeEtatCivilTest extends WebTestCase
{
    public function testUnJoueurSansEtatCivilResteVisibleDansLaListe(): void
    {
        $client = static::createClient();

        $gn = GnFactory::createOne();
        $sansEtatCivil = UserFactory::createOne(['email' => 'sans-etat-civil@test.local']);
        $avecEtatCivil = UserFactory::createOne([
            'email' => 'avec-etat-civil@test.local',
            'etatCivil' => EtatCivilFactory::createOne(['nom' => 'Dupont']),
        ]);

        ParticipantFactory::createOne(['gn' => $gn, 'user' => $sansEtatCivil]);
        ParticipantFactory::createOne(['gn' => $gn, 'user' => $avecEtatCivil]);

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ORGA']]));
        $crawler = $client->request('GET', '/gn/' . $gn->getId() . '/participants');

        static::assertResponseIsSuccessful();

        $texte = $crawler->filter('table')->text();
        static::assertStringContainsString('avec-etat-civil@test.local', $texte);
        static::assertStringContainsString('sans-etat-civil@test.local', $texte);
    }
}
