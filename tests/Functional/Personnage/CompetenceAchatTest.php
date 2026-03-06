<?php

declare(strict_types=1);

namespace App\Tests\Functional\Personnage;

use App\Tests\Factory\CompetenceFactory;
use App\Tests\Factory\CompetenceFamilyFactory;
use App\Tests\Factory\LevelFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for competence purchase and document access.
 *
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 *
 * @group functional
 */
class CompetenceAchatTest extends WebTestCase
{
    // -------------------------------------------------------------------------
    // Document access
    // -------------------------------------------------------------------------

    public function testPlayerCannotViewDocumentOfUnknownCompetence(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        $family = CompetenceFamilyFactory::createOne();
        $level = LevelFactory::createOne(['index' => 1, 'cout' => 5, 'cout_favori' => 3, 'cout_meconu' => 8]);
        $competence = CompetenceFactory::createOne(['competenceFamily' => $family, 'level' => $level]);
        $personnage = PersonnageFactory::createOne(['user' => $user]);

        // Personnage does NOT know this competence
        $client->loginUser($user);
        $client->request('GET', '/personnage/' . $personnage->getId() . '/competence/' . $competence->getId() . '/document');

        // Controller redirects with flash error: "Vous ne connaissez pas cette compétence !"
        self::assertResponseRedirects('/personnage/' . $personnage->getId());
    }

    // -------------------------------------------------------------------------
    // Buy competence
    // -------------------------------------------------------------------------

    public function testPlayerCanBuyCompetenceWithSufficientXp(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = UserFactory::createOne();
        $family = CompetenceFamilyFactory::createOne();
        $level = LevelFactory::createOne(['index' => 1, 'cout' => 5, 'cout_favori' => 3, 'cout_meconu' => 8]);
        $competence = CompetenceFactory::createOne(['competenceFamily' => $family, 'level' => $level]);
        // Personnage has 1000 XP — enough for any cost tier
        $personnage = PersonnageFactory::createOne(['user' => $user, 'xp' => 1000]);

        $client->loginUser($user);

        $crawler = $client->request('GET', '/personnage/' . $personnage->getId() . '/competence/add');
        self::assertResponseIsSuccessful();

        // The form field name is "form[id]" (built via createFormBuilder with name "form")
        // The choice value for a competence is its ID
        $form = $crawler->selectButton('Ajouter la compétence')->form([
            'form[id]' => (string) $competence->getId(),
        ]);
        $client->submit($form);

        // On success the controller redirects to the personnage detail page
        self::assertResponseRedirects();

        // XP should have been deducted — use find() after clear() to get fresh DB state
        $personnageId = $personnage->getId();
        $em->clear();
        $fresh = $em->find(\App\Entity\Personnage::class, $personnageId);
        self::assertLessThan(1000, $fresh->getXp());
    }

    public function testPlayerWithInsufficientXpCannotBuyCompetence(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne();
        $family = CompetenceFamilyFactory::createOne();
        $level = LevelFactory::createOne(['index' => 1, 'cout' => 50, 'cout_favori' => 30, 'cout_meconu' => 80]);
        $competence = CompetenceFactory::createOne(['competenceFamily' => $family, 'level' => $level]);
        // Personnage has 0 XP — cannot afford any non-free competence
        $personnage = PersonnageFactory::createOne(['user' => $user, 'xp' => 0]);

        $client->loginUser($user);

        $crawler = $client->request('GET', '/personnage/' . $personnage->getId() . '/competence/add');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Ajouter la compétence')->form([
            'form[id]' => (string) $competence->getId(),
        ]);
        $client->submit($form);

        // Controller re-renders the form (200) without deducting XP on failure
        self::assertResponseIsSuccessful();

        // XP must remain at 0 — no deduction on failure
        $personnageId = $personnage->getId();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $fresh = $em->find(\App\Entity\Personnage::class, $personnageId);
        self::assertSame(0, $fresh->getXp());
    }
}
