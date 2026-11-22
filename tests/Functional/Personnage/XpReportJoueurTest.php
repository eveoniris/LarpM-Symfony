<?php

declare(strict_types=1);

namespace App\Tests\Functional\Personnage;

use App\Entity\Personnage;
use App\Entity\User;
use App\Entity\XpReport;
use App\Service\XpReportCalculateur;
use App\Service\XpReportEligibilite;
use App\Tests\Factory\EspeceFactory;
use App\Tests\Factory\ExperienceGainFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

#[Group('functional')]
#[Group('rulebook')]
#[Group('XP-05')]
class XpReportJoueurTest extends WebTestCase
{
    /** Mort avec 8 XP acquis en jeu : tiers 3. */
    private function mort(User $joueur): Personnage
    {
        $mort = PersonnageFactory::createOne(['user' => $joueur, 'vivant' => false, 'xp' => 25]);
        ExperienceGainFactory::createOne(['personnage' => $mort, 'explanation' => XpReportCalculateur::LIBELLE_CREATION, 'xp_gain' => 17]);
        ExperienceGainFactory::createOne(['personnage' => $mort, 'explanation' => 'Participation', 'xp_gain' => 8]);

        return $mort;
    }

    private function onglet(KernelBrowser $client, Personnage $personnage): Crawler
    {
        EspeceFactory::findOrCreate(['nom' => 'Humain']);

        return $client->request('GET', \sprintf('/personnage/%d/detail/competences', $personnage->getId()));
    }

    public function testLeJoueurRecupereLeTiersDepuisSaFiche(): void
    {
        $client = static::createClient();
        $joueur = UserFactory::createOne();
        $mort = $this->mort($joueur);
        $vivant = PersonnageFactory::createOne(['user' => $joueur, 'xp' => 10]);
        $client->loginUser($joueur);

        $crawler = $this->onglet($client, $vivant);
        static::assertResponseIsSuccessful();
        $formulaire = $crawler->filter(\sprintf('form[action$="/personnage/%d/xp-report"]', $vivant->getId()));
        static::assertCount(1, $formulaire);
        static::assertStringContainsString('3 XP', $formulaire->text());

        $client->submit($formulaire->form());
        static::assertResponseRedirects();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        static::assertSame(13, $em->find(Personnage::class, $vivant->getId())->getXp());
        static::assertSame($mort->getId(), $em->getRepository(XpReport::class)->findOneBy([])->getPersonnageMort()->getId());

        // Plus rien à proposer : le mort a transmis, le personnage a reçu.
        $crawler = $this->onglet($client, $vivant);
        static::assertCount(0, $crawler->filter('form[action$="/xp-report"]'));
    }

    public function testRienNEstProposeSiLePersonnageADejaParticipe(): void
    {
        $client = static::createClient();
        $joueur = UserFactory::createOne();
        $this->mort($joueur);
        $vivant = PersonnageFactory::createOne(['user' => $joueur]);
        ParticipantFactory::createOne([
            'gn' => GnFactory::createOne(['date_debut' => new DateTime('-1 year')]),
            'personnage' => $vivant,
            'user' => $joueur,
        ]);
        $client->loginUser($joueur);

        $crawler = $this->onglet($client, $vivant);

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('form[action$="/xp-report"]'));
    }

    public function testUnAutreJoueurNeVoitRienEtNePeutPasPoster(): void
    {
        $client = static::createClient();
        $joueur = UserFactory::createOne();
        $mort = $this->mort($joueur);
        $vivant = PersonnageFactory::createOne(['user' => $joueur]);
        $client->loginUser($joueur);
        $crawler = $this->onglet($client, $vivant);
        $token = $crawler->filter('form[action$="/xp-report"] input[name=_token]')->attr('value');

        $client->loginUser(UserFactory::createOne());
        $client->request('POST', \sprintf('/personnage/%d/xp-report', $vivant->getId()), ['_token' => $token, 'mort' => $mort->getId()]);

        // L'application convertit l'accès refusé en redirection.
        static::assertResponseRedirects('/access_denied');
        static::assertNull(static::getContainer()->get(EntityManagerInterface::class)->getRepository(XpReport::class)->findOneBy([]));
    }

    public function testEligibiliteListeLesMortsDisponiblesEtLesBeneficiaires(): void
    {
        static::bootKernel();
        $joueur = UserFactory::createOne();
        $mort = $this->mort($joueur);
        PersonnageFactory::createOne(['user' => $joueur, 'vivant' => false]); // mort sans XP en jeu : rien à transmettre
        $libre = PersonnageFactory::createOne(['user' => $joueur]);
        $ancien = PersonnageFactory::createOne(['user' => $joueur]);
        ParticipantFactory::createOne([
            'gn' => GnFactory::createOne(['date_debut' => new DateTime('-2 years')]),
            'personnage' => $ancien,
            'user' => $joueur,
        ]);
        $futur = PersonnageFactory::createOne(['user' => $joueur]);
        ParticipantFactory::createOne([
            'gn' => GnFactory::createOne(['date_debut' => new DateTime('+3 months')]),
            'personnage' => $futur,
            'user' => $joueur,
        ]);

        $eligibilite = static::getContainer()->get(XpReportEligibilite::class);

        $morts = $eligibilite->mortsDisponibles($joueur);
        static::assertCount(1, $morts);
        static::assertSame($mort->getId(), $morts[0]['mort']->getId());
        static::assertSame(3, $morts[0]['tiers']);
        static::assertSame(4, $morts[0]['moitie']);

        $ids = array_map(static fn (Personnage $p): ?int => $p->getId(), $eligibilite->beneficiairesPossibles($joueur));
        static::assertContains($libre->getId(), $ids);
        static::assertContains($futur->getId(), $ids);
        static::assertNotContains($ancien->getId(), $ids);
    }
}
