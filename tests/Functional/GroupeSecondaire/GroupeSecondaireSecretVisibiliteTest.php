<?php

declare(strict_types=1);

namespace App\Tests\Functional\GroupeSecondaire;

use App\Entity\SecondaryGroup;
use App\Entity\SecondaryGroupType;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * SEC-01 : la liste des groupes transverses ne montre un groupe secret ni à un joueur sans personnage,
 * ni à un joueur qui n'en est pas membre. L'organisation le voit.
 *
 * DAMA bundle annule chaque test (transaction).
 */
#[Group('functional')]
#[Group('SEC-01')]
class GroupeSecondaireSecretVisibiliteTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->createGroups();
    }

    public function testJoueurSansPersonnageNeVoitPasLeGroupeSecret(): void
    {
        $this->client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER']]));
        $this->client->request('GET', '/groupeTransverse');

        static::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        static::assertStringContainsString('Guilde publique', $content);
        static::assertStringNotContainsString('Cercle des ombres', $content);
    }

    public function testJoueurNonMembreNeVoitPasLeGroupeSecret(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        PersonnageFactory::createOne(['user' => $user]);
        $this->client->loginUser($user);
        $this->client->request('GET', '/groupeTransverse');

        static::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        static::assertStringContainsString('Guilde publique', $content);
        static::assertStringNotContainsString('Cercle des ombres', $content);
    }

    public function testOrganisationVoitLeGroupeSecret(): void
    {
        $this->client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_GROUPE_TRANSVERSE']]));
        $this->client->request('GET', '/groupeTransverse');

        static::assertResponseIsSuccessful();
        static::assertStringContainsString('Cercle des ombres', (string) $this->client->getResponse()->getContent());
    }

    private function createGroups(): void
    {
        $type = new SecondaryGroupType();
        $type->setLabel('Confrérie');
        $this->em->persist($type);

        $groupes = [
            ['Guilde publique',   false],
            ['Cercle des ombres', true],
        ];

        foreach ($groupes as [$label, $secret]) {
            $group = new SecondaryGroup();
            $group->setLabel($label);
            $group->setDescription('Description du groupe.');
            $group->setSecondaryGroupType($type);
            $group->setSecret($secret);
            $group->setPrivate(false);
            $this->em->persist($group);
        }
        $this->em->flush();
    }
}
