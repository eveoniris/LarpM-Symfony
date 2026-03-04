<?php

declare(strict_types=1);

namespace App\Tests\Functional\Religion;

use App\Entity\PersonnagesReligions;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\ReligionFactory;
use App\Tests\Factory\ReligionLevelFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;

/**
 * Functional tests for religion addition restrictions.
 *
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 *
 * @group functional
 */
class ReligionTest extends WebTestCase
{
    use Factories;

    // -------------------------------------------------------------------------
    // Add religion
    // -------------------------------------------------------------------------

    public function testCanAddPratiquantReligion(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = UserFactory::createOne();
        $religion = ReligionFactory::createOne(['secret' => false]);
        $pratiquantLevel = ReligionLevelFactory::createOne(['index' => 1]);
        $personnage = PersonnageFactory::createOne(['user' => $user]);

        $client->loginUser($user->_real());

        $crawler = $client->request('GET', '/personnage/' . $personnage->getId() . '/addReligion');
        self::assertResponseIsSuccessful();

        // Pick the first available religion option value from the rendered select
        $firstOptionValue = $crawler
            ->filter('select[name="personnage_religion_form[religion]"] option')
            ->first()
            ->attr('value');

        $form = $crawler->selectButton('Valider votre religion')->form([
            'personnage_religion_form[religion]'      => $firstOptionValue,
            'personnage_religion_form[religionLevel]' => (string) $pratiquantLevel->getId(),
        ]);
        $client->submit($form);

        self::assertResponseRedirects();
    }

    public function testFanatiqueCannotAddAnotherReligion(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = UserFactory::createOne();
        $religion = ReligionFactory::createOne(['secret' => false]);
        $fanatiqueLevel = ReligionLevelFactory::createOne(['index' => 3]);
        $personnage = PersonnageFactory::createOne(['user' => $user]);

        // Make personnage a fanatique of a religion
        $personnageReligion = new PersonnagesReligions();
        $personnageReligion->setPersonnage($personnage->_real());
        $personnageReligion->setReligion($religion->_real());
        $personnageReligion->setReligionLevel($fanatiqueLevel->_real());
        $em->persist($personnageReligion);
        $em->flush();

        $client->loginUser($user->_real());
        $client->request('GET', '/personnage/' . $personnage->getId() . '/addReligion');

        // Controller immediately redirects with error for fanatique personnage
        self::assertResponseRedirects();
    }

    public function testCannotAddSecondFerventReligion(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = UserFactory::createOne();
        $religion1 = ReligionFactory::createOne(['secret' => false]);
        $religion2 = ReligionFactory::createOne(['secret' => false]);
        $ferventLevel = ReligionLevelFactory::createOne(['index' => 2]);
        $personnage = PersonnageFactory::createOne(['user' => $user]);

        // Personnage is already fervent for religion1
        $existingFervent = new PersonnagesReligions();
        $existingFervent->setPersonnage($personnage->_real());
        $existingFervent->setReligion($religion1->_real());
        $existingFervent->setReligionLevel($ferventLevel->_real());
        $em->persist($existingFervent);
        $em->flush();

        $client->loginUser($user->_real());

        // religion2 should appear as available (religion1 is already practiced)
        $crawler = $client->request('GET', '/personnage/' . $personnage->getId() . '/addReligion');
        self::assertResponseIsSuccessful();

        $firstOptionValue = $crawler
            ->filter('select[name="personnage_religion_form[religion]"] option')
            ->first()
            ->attr('value');

        // Try to add religion2 as fervent
        $form = $crawler->selectButton('Valider votre religion')->form([
            'personnage_religion_form[religion]'      => $firstOptionValue,
            'personnage_religion_form[religionLevel]' => (string) $ferventLevel->getId(),
        ]);
        $client->submit($form);

        // Controller redirects with error: already fervent of another religion
        self::assertResponseRedirects();
    }
}
