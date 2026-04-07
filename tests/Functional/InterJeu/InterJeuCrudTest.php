<?php

declare(strict_types=1);

namespace App\Tests\Functional\InterJeu;

use App\Entity\InterJeu;
use App\Entity\PersonnageChronologie;
use App\Enum\InterJeuEtat;
use App\Tests\Factory\InterJeuFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 */
#[Group('functional')]
class InterJeuCrudTest extends WebTestCase
{
    public function testDetailShowsParticipantCount(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_INTER_JEU']]);
        $interJeu = InterJeuFactory::createOne();

        $client->loginUser($user);
        $client->request('GET', '/inter-jeu/' . $interJeu->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a[href*="personnages"]');
    }

    public function testAddPersonnageToInterJeu(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_INTER_JEU']]);
        $interJeu = InterJeuFactory::createOne();
        $personnage = PersonnageFactory::createOne();

        $client->loginUser($user);
        $crawler = $client->request('GET', '/inter-jeu/' . $interJeu->getId() . '/personnage/add');

        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Ajouter')->form([
            'inter_jeu_personnage_add[personnage]' => $personnage->getId(),
        ]);
        $client->submit($form);

        self::assertResponseRedirects('/inter-jeu/' . $interJeu->getId() . '/personnages');

        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', (string) $personnage->getNom());
    }

    public function testRemovePersonnageFromInterJeu(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_INTER_JEU']]);
        $personnage = PersonnageFactory::createOne();
        $interJeu = InterJeuFactory::createOne();

        // Add personnage first
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $interJeuEntity = $em->find(InterJeu::class, $interJeu->getId());
        $personnageEntity = $em->find(\App\Entity\Personnage::class, $personnage->getId());
        $interJeuEntity->addPersonnage($personnageEntity);
        $em->flush();

        $client->loginUser($user);
        $client->request(
            'POST',
            '/inter-jeu/' . $interJeu->getId() . '/personnage/' . $personnage->getId() . '/remove',
        );

        self::assertResponseRedirects('/inter-jeu/' . $interJeu->getId() . '/personnages');
    }

    public function testChronologieButtonAppearsWhenDatePassed(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_INTER_JEU']]);
        $interJeu = InterJeuFactory::createOne([
            'dateReel' => new DateTime('-1 day'),
            'etat' => InterJeuEtat::TERMINE,
        ]);

        $client->loginUser($user);
        $client->request('GET', '/inter-jeu/' . $interJeu->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a[href*="chronologie"]');
    }

    public function testChronologieButtonAbsentWhenDateFuture(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_INTER_JEU']]);
        $interJeu = InterJeuFactory::createOne([
            'dateReel' => new DateTime('+30 days'),
        ]);

        $client->loginUser($user);
        $client->request('GET', '/inter-jeu/' . $interJeu->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('a[href*="chronologie"]');
    }

    public function testChronologieConfirmPageIsAccessible(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_INTER_JEU']]);
        $interJeu = InterJeuFactory::createOne([
            'dateReel' => new DateTime('-1 day'),
            'etat' => InterJeuEtat::TERMINE,
        ]);

        $client->loginUser($user);
        $client->request('GET', '/inter-jeu/' . $interJeu->getId() . '/chronologie');

        self::assertResponseIsSuccessful();
    }

    public function testChronologieConfirmCreatesEntries(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_INTER_JEU']]);
        $personnage = PersonnageFactory::createOne();
        $interJeu = InterJeuFactory::createOne([
            'dateReel' => new DateTime('-1 day'),
            'nom' => 'Test Inter',
            'anneeJeu' => 1050,
            'etat' => InterJeuEtat::TERMINE,
        ]);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $interJeuEntity = $em->find(InterJeu::class, $interJeu->getId());
        $personnageEntity = $em->find(\App\Entity\Personnage::class, $personnage->getId());
        $interJeuEntity->addPersonnage($personnageEntity);
        $em->flush();

        $client->loginUser($user);
        $crawler = $client->request('GET', '/inter-jeu/' . $interJeu->getId() . '/chronologie');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Confirmer la génération')->form();
        $client->submit($form);

        self::assertResponseRedirects('/inter-jeu/' . $interJeu->getId());

        $em->clear();
        $entries = $em->getRepository(PersonnageChronologie::class)->findBy([
            'personnage' => $personnage->getId(),
        ]);

        self::assertNotEmpty($entries);
        self::assertSame('Participation à Test Inter', $entries[0]->getEvenement());
        self::assertSame(1050, $entries[0]->getAnnee());
    }
}
