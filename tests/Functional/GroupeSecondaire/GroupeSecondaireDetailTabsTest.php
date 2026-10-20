<?php

declare(strict_types=1);

namespace App\Tests\Functional\GroupeSecondaire;

use App\Entity\Membre;
use App\Entity\Personnage;
use App\Entity\Postulant;
use App\Entity\SecondaryGroup;
use App\Entity\SecondaryGroupType;
use App\Entity\User;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Page détail d'un groupe transverse : un onglet par route, règles d'accès inchangées, budget de requêtes.
 *
 * DAMA bundle annule chaque test (transaction).
 */
#[Group('functional')]
class GroupeSecondaireDetailTabsTest extends WebTestCase
{
    private const TABS = ['presentation', 'membres', 'postulants', 'scenarisation', 'emails'];

    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    /** @return iterable<string, array{string}> */
    public static function tabs(): iterable
    {
        foreach (self::TABS as $tab) {
            yield $tab => [$tab];
        }
    }

    #[DataProvider('tabs')]
    public function testAdminSeesEveryTab(string $tab): void
    {
        $group = $this->createGroup(members: 3, postulants: 2);
        $admin = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_GROUPE_TRANSVERSE']]);

        $this->client->loginUser($admin);
        $this->client->request('GET', $this->url($group, $tab));

        static::assertResponseIsSuccessful();
    }

    public function testLegacyUrlAndAliasShowDefaultTab(): void
    {
        $group = $this->createGroup(members: 2);
        $admin = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_GROUPE_TRANSVERSE']]);
        $this->client->loginUser($admin);

        foreach (['groupeSecondaire', 'groupeTransverse'] as $prefix) {
            $this->client->request('GET', \sprintf('/%s/%d/detail', $prefix, $group->getId()));
            static::assertResponseIsSuccessful();
            static::assertSelectorExists('[aria-current="page"], .nav-link.active');

            $this->client->request('GET', \sprintf('/%s/%d/detail/membres', $prefix, $group->getId()));
            static::assertResponseIsSuccessful();
        }
    }

    public function testUnknownTabIsNotFound(): void
    {
        $group = $this->createGroup();
        $admin = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_GROUPE_TRANSVERSE']]);
        $this->client->loginUser($admin);
        $this->client->request('GET', $this->url($group, 'inconnu'));

        static::assertResponseStatusCodeSame(404);
    }

    public function testChefManagesButDoesNotSeeScenarisation(): void
    {
        $group = $this->createGroup(members: 2, postulants: 1);
        $chef = $group->getResponsable()->getUser();
        $this->client->loginUser($chef);

        foreach (['presentation', 'membres', 'postulants', 'emails'] as $tab) {
            $this->client->request('GET', $this->url($group, $tab));
            static::assertResponseIsSuccessful($tab);
        }

        $this->client->request('GET', $this->url($group, 'scenarisation'));
        static::assertResponseRedirects($this->url($group, 'presentation', true));
    }

    public function testMemberWithoutSecretCannotSeePostulantsNorEmails(): void
    {
        $group = $this->createGroup(members: 2, postulants: 1);
        $member = $this->firstSimpleMember($group);
        $this->client->loginUser($member->getPersonnage()->getUser());

        foreach (['presentation', 'membres'] as $tab) {
            $this->client->request('GET', $this->url($group, $tab));
            static::assertResponseIsSuccessful($tab);
        }

        foreach (['postulants', 'emails', 'scenarisation'] as $tab) {
            $this->client->request('GET', $this->url($group, $tab));
            static::assertResponseRedirects($this->url($group, 'presentation', true));
        }
    }

    public function testMemberWithSecretSeesSecretAndPostulantsButNotEmails(): void
    {
        $group = $this->createGroup(members: 2, postulants: 1);
        $member = $this->firstSimpleMember($group);
        $member->setSecret(true);
        $this->em->flush();
        $this->client->loginUser($member->getPersonnage()->getUser());

        $this->client->request('GET', $this->url($group, 'presentation'));
        static::assertResponseIsSuccessful();
        static::assertStringContainsString('Description secrète', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', $this->url($group, 'postulants'));
        static::assertResponseIsSuccessful();

        $this->client->request('GET', $this->url($group, 'emails'));
        static::assertResponseRedirects();
    }

    public function testSecretDescriptionHiddenFromMemberWithoutSecret(): void
    {
        $group = $this->createGroup(members: 2);
        $member = $this->firstSimpleMember($group);
        $this->client->loginUser($member->getPersonnage()->getUser());

        $this->client->request('GET', $this->url($group, 'presentation'));

        static::assertResponseIsSuccessful();
        static::assertStringNotContainsString('Description secrète', (string) $this->client->getResponse()->getContent());
    }

    public function testSecretGroupIsClosedToOutsiders(): void
    {
        $group = $this->createGroup(members: 1, secret: true);
        $outsider = PersonnageFactory::createOne()->getUser();
        $this->client->loginUser($outsider);

        foreach (self::TABS as $tab) {
            $this->client->request('GET', $this->url($group, $tab));
            static::assertFalse($this->client->getResponse()->isSuccessful(), $tab);
        }
    }

    public function testAnonymousGroupHidesCharactersFromMembersButNotFromChef(): void
    {
        $group = $this->createGroup(members: 2, private: true);
        $member = $this->firstSimpleMember($group);

        $this->client->loginUser($member->getPersonnage()->getUser());
        $this->client->request('GET', $this->url($group, 'membres'));
        static::assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        static::assertStringContainsString('Vous ne pouvez distinguer les membres', $html);
        static::assertSelectorNotExists('.gs-avatar');

        $this->client->restart();
        $this->client->loginUser($group->getResponsable()->getUser());
        $this->client->request('GET', $this->url($group, 'membres'));
        static::assertResponseIsSuccessful();
        static::assertSelectorExists('.gs-avatar');
    }

    public function testBetaInterfaceRendersEveryTab(): void
    {
        $group = $this->createGroup(members: 2, postulants: 1);
        $admin = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_GROUPE_TRANSVERSE']]);
        $admin->setUiTheme('beta');
        $this->em->flush();
        $this->client->loginUser($admin);

        foreach (self::TABS as $tab) {
            $this->client->request('GET', $this->url($group, $tab));
            static::assertResponseIsSuccessful($tab);
        }

        $this->client->request('GET', $this->url($group, 'membres'));
        static::assertSelectorExists('.lm-member');
    }

    public function testBetaMembersTabOffersShowMoreBeyondTwentyFiveRows(): void
    {
        $group = $this->createGroup(members: 30);
        $admin = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_GROUPE_TRANSVERSE']]);
        $admin->setUiTheme('beta');
        $this->em->flush();
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', $this->url($group, 'membres'));
        static::assertResponseIsSuccessful();
        static::assertSelectorExists('[data-lm-more="25"] [data-lm-more-search]');
        static::assertSelectorExists('[data-lm-more-button]');
        static::assertSame(31, $crawler->filter('[data-lm-row]')->count());
    }

    public function testNonBetaMembersTabHasNoShowMore(): void
    {
        $group = $this->createGroup(members: 30);
        $admin = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_GROUPE_TRANSVERSE']]);
        $this->client->loginUser($admin);

        $this->client->request('GET', $this->url($group, 'membres'));
        static::assertResponseIsSuccessful();
        static::assertSelectorNotExists('[data-lm-more], [data-lm-row]');
    }

    public function testMembersTabQueryCountDoesNotGrowWithMemberCount(): void
    {
        $small = $this->createGroup(members: 3);
        $large = $this->createGroup(members: 25);
        $admin = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_GROUPE_TRANSVERSE']]);
        $this->client->loginUser($admin);

        $smallCount = $this->countQueries($this->url($small, 'membres'));
        $largeCount = $this->countQueries($this->url($large, 'membres'));

        static::assertLessThanOrEqual($smallCount + 2, $largeCount, 'Le nombre de requêtes ne doit pas dépendre du nombre de membres.');
        static::assertLessThan(60, $largeCount);
    }

    /** @return int[] */
    #[DataProvider('tabs')]
    public function testEveryTabStaysUnderQueryBudget(string $tab): void
    {
        $group = $this->createGroup(members: 12, postulants: 4);
        $admin = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_GROUPE_TRANSVERSE']]);
        $this->client->loginUser($admin);

        static::assertLessThan(60, $this->countQueries($this->url($group, $tab)));
    }

    private function countQueries(string $url): int
    {
        $this->client->request('GET', $url); // échauffement : le second appel redémarre le noyau, le compteur ne contient plus la préparation des données
        $this->client->enableProfiler();
        $this->client->request('GET', $url);
        static::assertResponseIsSuccessful();
        $profile = $this->client->getProfile();
        static::assertNotFalse($profile, 'Profiler indisponible.');

        return $profile->getCollector('db')->getQueryCount();
    }

    private function url(SecondaryGroup $group, string $tab, bool $default = false): string
    {
        if ($default) {
            return \sprintf('/groupeSecondaire/%d/detail', $group->getId());
        }

        return \sprintf('/groupeSecondaire/%d/detail/%s', $group->getId(), $tab);
    }

    private function firstSimpleMember(SecondaryGroup $group): Membre
    {
        foreach ($group->getMembres() as $membre) {
            if ($membre->getPersonnage()->getId() !== $group->getResponsable()->getId()) {
                return $membre;
            }
        }

        throw new \LogicException('Aucun membre simple.');
    }

    private function createGroup(int $members = 0, int $postulants = 0, bool $secret = false, bool $private = false): SecondaryGroup
    {
        $type = new SecondaryGroupType();
        $type->setLabel('Ordre');
        $this->em->persist($type);

        $chef = $this->createPersonnage();

        $group = new SecondaryGroup();
        $group->setLabel('Confrérie du savoir');
        $group->setDescription(str_repeat("Ils savent beaucoup de choses.\n\n", 20));
        $group->setDescriptionSecrete('Le secret de la confrérie.');
        $group->setMateriel('Un parchemin');
        $group->setSecret($secret);
        $group->setPrivate($private);
        $group->setShowDiscord(false);
        $group->setSecondaryGroupType($type);
        $group->setResponsable($chef);
        $this->em->persist($group);

        $chefMembre = new Membre();
        $chefMembre->setPersonnage($chef);
        $chefMembre->setSecondaryGroup($group);
        $chefMembre->setSecret(true);
        $this->em->persist($chefMembre);
        $group->addMembre($chefMembre);

        for ($i = 0; $i < $members; ++$i) {
            $membre = new Membre();
            $membre->setPersonnage($this->createPersonnage());
            $membre->setSecondaryGroup($group);
            $membre->setSecret(false);
            $this->em->persist($membre);
            $group->addMembre($membre);
        }

        for ($i = 0; $i < $postulants; ++$i) {
            $postulant = new Postulant();
            $postulant->setPersonnage($this->createPersonnage());
            $postulant->setSecondaryGroup($group);
            $postulant->setExplanation('Je souhaite rejoindre la confrérie.');
            $postulant->setWaiting(false);
            $postulant->setDate(new DateTime());
            $this->em->persist($postulant);
            $group->addPostulant($postulant);
        }

        $this->em->flush();
        $this->em->clear();

        return $this->em->getRepository(SecondaryGroup::class)->find($group->getId());
    }

    private function createPersonnage(): Personnage
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $personnage = PersonnageFactory::createOne(['user' => $user]);
        $user->setPersonnage($personnage);
        $this->em->flush();

        return $personnage;
    }
}
