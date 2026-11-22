<?php

declare(strict_types=1);

namespace App\Tests\Functional\Participant;

use App\Entity\Participant;
use App\Tests\Factory\BilletFactory;
use App\Tests\Factory\ExperienceGainFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
#[Group('rulebook')]
#[Group('XP-05')]
class PersonnageNewXpReportTest extends WebTestCase
{
    private function participant(object $joueur): Participant
    {
        $gn = GnFactory::createOne();

        return ParticipantFactory::createOne([
            'gn' => $gn,
            'user' => $joueur,
            'billet' => BilletFactory::createOne(['gn' => $gn, 'user' => $joueur]),
            'groupeGn' => GroupeGnFactory::createOne(['gn' => $gn]),
        ]);
    }

    public function testLeFormulaireProposeLeBonusDUnMort(): void
    {
        $client = static::createClient();
        $joueur = UserFactory::createOne();
        $mort = PersonnageFactory::createOne(['user' => $joueur, 'vivant' => false]);
        ExperienceGainFactory::createOne(['personnage' => $mort, 'explanation' => 'Participation', 'xp_gain' => 8]);
        $participant = $this->participant($joueur);
        $client->loginUser($joueur);

        $crawler = $client->request('GET', \sprintf('/participant/%d/personnageNew', $participant->getId()));

        static::assertResponseIsSuccessful();
        $options = $crawler->filter('select[name$="[xpReportMort]"] option');
        static::assertCount(2, $options); // placeholder + le mort
        static::assertStringContainsString('3 XP', $options->eq(1)->text());
    }

    public function testRienNEstProposeSansMortDisponible(): void
    {
        $client = static::createClient();
        $joueur = UserFactory::createOne();
        $participant = $this->participant($joueur);
        $client->loginUser($joueur);

        $crawler = $client->request('GET', \sprintf('/participant/%d/personnageNew', $participant->getId()));

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('select[name$="[xpReportMort]"]'));
    }
}
