<?php

declare(strict_types=1);

namespace App\Tests\Functional\Personnage;

use App\Entity\Personnage;
use App\Entity\PersonnageLignee;
use App\Entity\User;
use App\Entity\XpReport;
use App\Service\XpReportCalculateur;
use App\Service\XpReportHeritiers;
use App\Service\ConceptionRegles;
use App\Tests\Factory\AgeFactory;
use App\Tests\Factory\ClasseFactory;
use App\Tests\Factory\EspeceFactory;
use App\Tests\Factory\GenreFactory;
use App\Tests\Factory\TerritoireFactory;
use App\Tests\Factory\ExperienceGainFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

// @mago-expect lint:too-many-methods
#[Group('functional')]
#[Group('rulebook')]
#[Group('XP-07')]
#[Group('XP-06')]
class XpReportScenaristeTest extends WebTestCase
{
    private function scenariste(): User
    {
        return UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_SCENARISTE']]);
    }

    private function admin(): User
    {
        return UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]);
    }

    /** Mort avec 8 XP acquis en jeu : tiers 3, moitié 4. */
    private function mort(User $joueur): Personnage
    {
        $mort = PersonnageFactory::createOne(['user' => $joueur, 'vivant' => false, 'xp' => 25]);
        ExperienceGainFactory::createOne(['personnage' => $mort, 'explanation' => XpReportCalculateur::LIBELLE_CREATION, 'xp_gain' => 17]);
        ExperienceGainFactory::createOne(['personnage' => $mort, 'explanation' => 'Participation', 'xp_gain' => 8]);

        return $mort;
    }

    private function lier(Personnage $enfant, Personnage $parent): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $lien = new PersonnageLignee();
        $lien->setPersonnage($enfant);
        $lien->setParent1($parent);
        $lien->setParent2(null);
        $lien->setLignee(null);
        $em->persist($lien);
        $em->flush();
    }

    private function page(KernelBrowser $client, Personnage $mort): Crawler
    {
        EspeceFactory::findOrCreate(['nom' => 'Humain']);

        return $client->request('GET', \sprintf('/personnage/%d/xp-report/scenariste', $mort->getId()));
    }

    private function poster(KernelBrowser $client, Crawler $crawler, int $cibleId, array $extra = []): void
    {
        $token = $crawler->filter('input[name=_token]')->first()->attr('value');
        $client->request('POST', $client->getRequest()->getPathInfo() . '/reporter', ['_token' => $token, 'cible' => $cibleId, ...$extra]);
    }

    public function testLeTiersVaVersUnAutrePersonnageDuJoueur(): void
    {
        $client = static::createClient();
        $joueur = UserFactory::createOne();
        $mort = $this->mort($joueur);
        $autre = PersonnageFactory::createOne(['user' => $joueur, 'xp' => 10]);
        $client->loginUser($this->scenariste());

        $crawler = $this->page($client, $mort);
        static::assertResponseIsSuccessful();
        static::assertStringContainsString('Transmettre 3 XP (tiers)', $crawler->text());
        $this->poster($client, $crawler, $autre->getId());

        static::assertResponseRedirects();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        static::assertSame(13, $em->find(Personnage::class, $autre->getId())->getXp());
        static::assertSame(XpReportCalculateur::MODE_TIERS, $em->getRepository(XpReport::class)->findOneBy([])->getMode());
    }

    public function testHeritierSansConceptionDemandeUnForcageAvecMotifPuisRecoitLaMoitie(): void
    {
        $client = static::createClient();
        $mort = $this->mort(UserFactory::createOne());
        $heritier = PersonnageFactory::createOne(['user' => null, 'xp' => 20]);
        $this->lier($heritier, $mort);
        $client->loginUser($this->scenariste());

        $crawler = $this->page($client, $mort);
        static::assertStringContainsString('conception inconnue', $crawler->text());

        // Sans forçage : avertissement, rien n'est transmis.
        $this->poster($client, $crawler, $heritier->getId());
        $em = static::getContainer()->get(EntityManagerInterface::class);
        static::assertNull($em->getRepository(XpReport::class)->findOneBy([]));

        // Forçage sans motif : refusé.
        $crawler = $this->page($client, $mort);
        $this->poster($client, $crawler, $heritier->getId(), ['forcer' => '1', 'motif' => ' ']);
        static::assertNull($em->getRepository(XpReport::class)->findOneBy([]));

        // Forçage avec motif : la moitié est transmise.
        $crawler = $this->page($client, $mort);
        $this->poster($client, $crawler, $heritier->getId(), ['forcer' => '1', 'motif' => 'Conception hors application']);
        $em->clear();
        $report = $em->getRepository(XpReport::class)->findOneBy([]);
        static::assertSame(XpReportCalculateur::MODE_MOITIE, $report->getMode());
        static::assertSame(4, $report->getXpReporte());
        static::assertSame(24, $em->find(Personnage::class, $heritier->getId())->getXp());
    }

    public function testCreationDUnHeritierDepuisLeDefuntLuiTransmetLaMoitie(): void
    {
        $client = static::createClient();
        $mort = $this->mort(UserFactory::createOne());
        $client->loginUser($this->scenariste());
        $em = static::getContainer()->get(EntityManagerInterface::class);
        AgeFactory::findOrCreate(['label' => ConceptionRegles::AGE_HERITIER, 'enableCreation' => false, 'bonus' => 99]);
        AgeFactory::createOne(['label' => 'Jeune adulte', 'enableCreation' => true, 'minimumValue' => -100, 'bonus' => 3]);
        ClasseFactory::findOrCreate(['secrete' => true]);
        $genre = GenreFactory::findOrCreate(['label' => 'Masculin']);
        $territoire = TerritoireFactory::createOne();
        GnFactory::createOne(['actif' => true, 'xp_creation' => 10, 'date_debut' => new DateTime('+1 year'), 'date_fin' => new DateTime('+1 year +3 days')]);

        $crawler = $this->page($client, $mort);
        $token = $crawler->filter('input[name=_token]')->first()->attr('value');
        $client->request('POST', \sprintf('/personnage/%d/xp-report/scenariste/heritier', $mort->getId()), [
            '_token' => $token,
            'nom' => 'Petit Conan',
            'genre' => $genre->getId(),
            'territoire' => $territoire->getId(),
        ]);

        static::assertResponseRedirects();
        $em->clear();
        $report = $em->getRepository(XpReport::class)->findOneBy([]);
        static::assertNotNull($report);
        static::assertSame(XpReportCalculateur::MODE_MOITIE, $report->getMode());
        $heritier = $report->getPersonnageBeneficiaire();
        static::assertSame('Petit Conan', $heritier->getNom());
        // 10 XP de création (dernier GN actif) + 3 XP de bonus d'âge (premier âge jouable) + 4 XP de bonus.
        static::assertSame(17, $heritier->getXp());
        static::assertNotNull($em->getRepository(PersonnageLignee::class)->findOneBy(['personnage' => $heritier]));
    }

    public function testUnJoueurSimpleNAPasAcces(): void
    {
        $client = static::createClient();
        $joueur = UserFactory::createOne();
        $mort = $this->mort($joueur);
        $client->loginUser($joueur);

        $client->request('GET', \sprintf('/personnage/%d/xp-report/scenariste', $mort->getId()));

        static::assertResponseRedirects('/access_denied');
    }

    public function testAnnulationParLeScenaristeRetireLeBonus(): void
    {
        $client = static::createClient();
        $joueur = UserFactory::createOne();
        $mort = $this->mort($joueur);
        $autre = PersonnageFactory::createOne(['user' => $joueur, 'xp' => 10]);
        $client->loginUser($this->scenariste());
        $crawler = $this->page($client, $mort);
        $this->poster($client, $crawler, $autre->getId());

        $crawler = $this->page($client, $mort);
        $token = $crawler->filter('input[name=_token]')->first()->attr('value');
        $client->request('POST', \sprintf('/personnage/%d/xp-report/scenariste/annuler', $mort->getId()), ['_token' => $token, 'motif' => 'Erreur']);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        static::assertNull($em->getRepository(XpReport::class)->findOneBy([]));
        static::assertSame(10, $em->find(Personnage::class, $autre->getId())->getXp());
    }

    private function soumettreStatut(KernelBrowser $client, Personnage $personnage, array $valeurs): void
    {
        EspeceFactory::findOrCreate(['nom' => 'Humain']);
        $crawler = $client->request('GET', \sprintf('/personnage/%d/statut', $personnage->getId()));
        static::assertResponseIsSuccessful();
        $client->submit($crawler->selectButton('Valider')->form(), $valeurs);
    }

    public function testResurrectionRefuseeSiLeBonusEstTransmisSaufAdminAvecConfirmationEtMotif(): void
    {
        $client = static::createClient();
        $joueur = UserFactory::createOne();
        $mort = $this->mort($joueur);
        $autre = PersonnageFactory::createOne(['user' => $joueur, 'xp' => 10]);
        $client->loginUser($this->scenariste());
        $this->poster($client, $this->page($client, $mort), $autre->getId());
        $em = static::getContainer()->get(EntityManagerInterface::class);

        // Un scénariste ne peut pas ressusciter.
        $this->soumettreStatut($client, $mort, ['personnage_statut[vivant]' => '1']);
        $em->clear();
        static::assertFalse($em->find(Personnage::class, $mort->getId())->getVivant());
        static::assertNotNull($em->getRepository(XpReport::class)->findOneBy([]));

        // Un admin doit confirmer et donner un motif.
        $client->loginUser($this->admin());
        $this->soumettreStatut($client, $mort, ['personnage_statut[vivant]' => '1']);
        $em->clear();
        static::assertFalse($em->find(Personnage::class, $mort->getId())->getVivant());

        $this->soumettreStatut($client, $mort, [
            'personnage_statut[vivant]' => '1',
            'personnage_statut[confirmerRetraitBonus]' => '1',
            'personnage_statut[motifRetraitBonus]' => 'Erreur de mort',
        ]);
        $em->clear();
        static::assertTrue($em->find(Personnage::class, $mort->getId())->getVivant());
        static::assertNull($em->getRepository(XpReport::class)->findOneBy([]));
        static::assertSame(10, $em->find(Personnage::class, $autre->getId())->getXp());
    }

    public function testTuerUnPersonnageMeneALEcranDuBonus(): void
    {
        $client = static::createClient();
        $vivant = PersonnageFactory::createOne(['user' => UserFactory::createOne(), 'vivant' => true, 'xp' => 25]);
        ExperienceGainFactory::createOne(['personnage' => $vivant, 'explanation' => 'Participation', 'xp_gain' => 8]);
        $client->loginUser($this->scenariste());

        $this->soumettreStatut($client, $vivant, ['personnage_statut[vivant]' => '0']);

        static::assertResponseRedirects(\sprintf('/personnage/%d/xp-report/scenariste', $vivant->getId()));
    }

    public function testGnEcoulesDepuisLaConception(): void
    {
        static::bootKernel();
        $conception = GnFactory::createOne(['date_debut' => new DateTime('-10 years'), 'date_fin' => new DateTime('-10 years +3 days')]);
        foreach ([-8, -6, -4] as $annees) {
            GnFactory::createOne(['date_debut' => new DateTime($annees . ' years'), 'date_fin' => new DateTime($annees . ' years +3 days')]);
        }
        GnFactory::createOne(['date_debut' => new DateTime('+1 year'), 'date_fin' => new DateTime('+1 year +3 days')]);

        $heritiers = static::getContainer()->get(XpReportHeritiers::class);

        static::assertSame(3, $heritiers->gnEcoules($conception));
    }
}
