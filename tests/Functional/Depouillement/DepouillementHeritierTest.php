<?php

declare(strict_types=1);

namespace App\Tests\Functional\Depouillement;

use App\Entity\Conception;
use App\Entity\Gn;
use App\Entity\Personnage;
use App\Entity\User;
use App\Enum\ConceptionIssue;
use App\Enum\ConceptionStatut;
use App\Service\ConceptionRegles;
use App\Tests\Factory\AgeFactory;
use App\Tests\Factory\ClasseFactory;
use App\Tests\Factory\GenreFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\TerritoireFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Écran du scénariste : droits, décision (plus tard, clôture), création de l'héritier, simulation sans action.
 */
#[Group('functional')]
class DepouillementHeritierTest extends WebTestCase
{
    private function scenariste(): User
    {
        return UserFactory::createOne(['username' => 'scenar', 'roles' => ['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_SCENARISTE']]);
    }

    private function conception(Gn $gn, bool $simulation = false): Conception
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $conception = (new Conception())
            ->setGn($gn)
            ->setParent1(PersonnageFactory::createOne())
            ->setParent2(PersonnageFactory::createOne())
            ->setSimulation($simulation)
            ->setTirage(12)
            ->setIssue(ConceptionIssue::GARCON);
        $em->persist($conception);
        $em->flush();

        return $conception;
    }

    private function conceptions(): string
    {
        return '/gn/%d/conceptions';
    }

    public function testUnBetaTesteurSansRoleDeValidationEstRefuse(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $client->loginUser(UserFactory::createOne(['username' => 'beta', 'roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]));

        $client->request('GET', \sprintf($this->conceptions(), $gn->getId()));

        static::assertResponseRedirects('/access_denied');
    }

    public function testLeScenaristeVoitLesConceptionsDuGn(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $conception = $this->conception($gn);
        $client->loginUser($this->scenariste());

        $client->request('GET', \sprintf($this->conceptions(), $gn->getId()));

        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('body', 'Tirage : 12');
        static::assertSelectorTextContains('body', $conception->getParent1()->getNom());
    }

    public function testUneSimulationNeProposeAucuneAction(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $this->conception($gn, true);
        $client->loginUser($this->scenariste());

        $client->request('GET', \sprintf($this->conceptions(), $gn->getId()));

        static::assertSelectorTextContains('body', 'simulation');
        static::assertSelectorNotExists('button[formaction$="/plus-tard"]');
        static::assertSelectorNotExists('details');
    }

    public function testGererPlusTardExigeUneRemarque(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $conception = $this->conception($gn);
        $client->loginUser($this->scenariste());

        $crawler = $client->request('GET', \sprintf($this->conceptions(), $gn->getId()));
        $bouton = $crawler->filter('button[formaction$="/plus-tard"]')->form(['remarque' => '']);
        $client->submit($bouton);
        $client->followRedirect();
        static::assertSelectorTextContains('body', 'remarque est obligatoire');

        $crawler = $client->request('GET', \sprintf($this->conceptions(), $gn->getId()));
        $client->submit($crawler->filter('button[formaction$="/plus-tard"]')->form(['remarque' => 'En jeu, à l\'Opus 3']));
        $client->followRedirect();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $relue = $em->getRepository(Conception::class)->find($conception->getId());
        static::assertSame(ConceptionStatut::A_GERER_PLUS_TARD, $relue?->getStatut());
        static::assertSame('En jeu, à l\'Opus 3', $relue->getRemarque());
    }

    public function testCreerUnHeritierDepuisLecran(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        if (null === $em->getRepository(\App\Entity\Age::class)->findOneBy(['label' => ConceptionRegles::AGE_HERITIER])) {
            AgeFactory::createOne(['label' => ConceptionRegles::AGE_HERITIER, 'enableCreation' => false]);
        }

        if (null === $em->getRepository(\App\Entity\Classe::class)->findOneBy(['secrete' => true])) {
            ClasseFactory::createOne(['secrete' => true]);
        }

        $gn = GnFactory::createOne(['xp_creation' => 10]);
        $conception = $this->conception($gn);
        $genre = GenreFactory::createOne();
        $territoire = TerritoireFactory::createOne();
        $client->loginUser($this->scenariste());

        $crawler = $client->request('GET', \sprintf($this->conceptions(), $gn->getId()));
        $prefixe = 'heritiers_' . $conception->getId();
        $client->submit($crawler
            ->selectButton('Créer le ou les héritiers')
            ->form([
                $prefixe . '[premier][nom]' => 'Petit Conan',
                $prefixe . '[premier][genre]' => (string) $genre->getId(),
                $prefixe . '[premier][territoire]' => (string) $territoire->getId(),
            ]));
        $client->followRedirect();

        static::assertSelectorTextContains('body', 'Petit Conan');
        $em->clear();
        $relue = $em->getRepository(Conception::class)->find($conception->getId());
        static::assertSame(ConceptionStatut::HERITIERS_CREES, $relue?->getStatut());
        static::assertCount(1, $relue->getHeritiers());
        $heritier = $em->getRepository(Personnage::class)->find($relue->getHeritiers()[0]);
        static::assertNull($heritier?->getUser());
        static::assertSame(0, $heritier->getAgeReel());
        static::assertSame(10, $heritier->getXp());
    }
}
