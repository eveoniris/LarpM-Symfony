<?php

declare(strict_types=1);

namespace App\Tests\Functional\GroupeSecondaire;

use App\Entity\SecondaryGroup;
use App\Entity\SecondaryGroupType;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Liste des groupes transverses : URL canonique et alias, badge de type, pagination (résumé, limite), rendu inchangé hors bêta.
 *
 * DAMA bundle annule chaque test (transaction).
 */
#[Group('functional')]
class GroupeSecondaireListTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCanonicalUrlAndAliasBothWork(): void
    {
        $this->createGroups(2);
        $this->client->loginUser($this->admin(false));

        foreach (['/groupeTransverse', '/groupeSecondaire'] as $url) {
            $this->client->request('GET', $url);
            static::assertResponseIsSuccessful($url);
        }

        $router = static::getContainer()->get('router');
        static::assertSame('/groupeTransverse', $router->generate('groupeSecondaire.list'));
        static::assertSame('/groupeTransverse', $router->generate('groupeTransverse.list'));
    }

    public function testBetaShowsTypeBadgeWithIconAndPagerSummary(): void
    {
        $this->createGroups(30);
        $this->client->loginUser($this->admin(true));

        $crawler = $this->client->request('GET', '/groupeTransverse');
        static::assertResponseIsSuccessful();

        static::assertSelectorExists('td .lm-badge .fa-book-open');
        static::assertSelectorTextContains('.lm-pager-summary', '1 à 25 sur 30');
        static::assertGreaterThan(0, $crawler->filter('.lm-pager-pages [aria-current="page"]')->count());
        static::assertSelectorExists('.lm-pager-size a[aria-current="true"]');
    }

    public function testBetaLimitInUrlIsKept(): void
    {
        $this->createGroups(60);
        $this->client->loginUser($this->admin(true));

        $this->client->request('GET', '/groupeTransverse?limit=50');
        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('.lm-pager-summary', '1 à 50 sur 60');
        static::assertSelectorExists('.lm-pager-pages a[href*="limit=50"]');
    }

    public function testNonBetaRendersLegacyMarkup(): void
    {
        $this->createGroups(30);
        $this->client->loginUser($this->admin(false));

        $this->client->request('GET', '/groupeTransverse');
        static::assertResponseIsSuccessful();

        static::assertSelectorNotExists('.lm-pager, .lm-badge, .lm-list-filters');
        static::assertSelectorExists('ul.pagination');
        static::assertSelectorExists('table.table-striped');
    }

    private function admin(bool $beta): object
    {
        $admin = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_GROUPE_TRANSVERSE']]);
        $admin->setUiTheme($beta ? 'beta' : null);
        $this->em->flush();

        return $admin;
    }

    private function createGroups(int $count): void
    {
        $type = new SecondaryGroupType();
        $type->setLabel('Confrérie');
        $this->em->persist($type);

        for ($i = 1; $i <= $count; ++$i) {
            $group = new SecondaryGroup();
            $group->setLabel(\sprintf('Groupe test %02d', $i));
            $group->setDescription('Description du groupe.');
            $group->setSecondaryGroupType($type);
            $group->setSecret(false);
            $group->setPrivate(false);
            $this->em->persist($group);
        }
        $this->em->flush();
    }
}
