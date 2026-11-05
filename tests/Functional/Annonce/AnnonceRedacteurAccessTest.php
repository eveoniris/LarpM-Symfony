<?php

declare(strict_types=1);

namespace App\Tests\Functional\Annonce;

use App\Entity\Annonce;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Le rôle rédacteur (seul, sans autre rôle) doit pouvoir gérer les annonces.
 */
#[Group('functional')]
class AnnonceRedacteurAccessTest extends WebTestCase
{
    public function testRedacteurCanListAnnonces(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_REDACTEUR']]));

        $client->request('GET', '/annonce');

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('a[href="/annonce/add"]');
    }

    public function testRedacteurCanOpenAddForm(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_REDACTEUR']]));

        $client->request('GET', '/annonce/add');

        static::assertResponseIsSuccessful();
    }

    public function testRedacteurCanAddAnnonce(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_REDACTEUR']]));
        $gn = GnFactory::createOne();

        $crawler = $client->request('GET', '/annonce/add');
        $form = $crawler
            ->selectButton('Sauvegarder')
            ->form([
                'annonce[title]' => 'Annonce de test rédacteur',
                'annonce[archive]' => '0',
                'annonce[gn]' => (string) $gn->getId(),
                'annonce[text]' => '<p>Texte</p>',
            ]);
        $client->submit($form);

        static::assertResponseRedirects('/annonce');

        $annonce = static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(Annonce::class)
            ->findOneBy(['title' => 'Annonce de test rédacteur']);
        static::assertNotNull($annonce);
    }

    public function testSimpleUserCannotAddAnnonce(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER']]));

        $client->request('GET', '/annonce/add');

        static::assertResponseRedirects('/access_denied');
    }

    public function testHomepageOffersAnnonceLinksToRedacteur(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_REDACTEUR']]));

        $client->request('GET', '/');

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('a[href="/annonce/add"]');
    }
}
