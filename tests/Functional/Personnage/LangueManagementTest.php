<?php

declare(strict_types=1);

namespace App\Tests\Functional\Personnage;

use App\Entity\Personnage;
use App\Entity\PersonnageLangues;
use App\Entity\PersonnageTrigger;
use App\Tests\Factory\LangueFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels de la page de gestion des langues d'un personnage.
 *
 * DAMA enveloppe chaque test dans une transaction annulée automatiquement.
 */
#[Group('functional')]
class LangueManagementTest extends WebTestCase
{
    public function testManagePageRendersForOrga(): void
    {
        $client = static::createClient();
        $orga = UserFactory::createOne(['roles' => ['ROLE_ORGA']]);
        $personnage = PersonnageFactory::createOne();

        $client->loginUser($orga);
        $client->request('GET', '/personnage/' . $personnage->getId() . '/updateLangue');

        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('body', 'Ajouter une langue');
    }

    public function testPlayerCannotAccessManagePage(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $personnage = PersonnageFactory::createOne(['user' => $user]);

        $client->loginUser($user);
        $client->request('GET', '/personnage/' . $personnage->getId() . '/updateLangue');

        // Le pattern projet redirige les accès refusés vers /access_denied
        static::assertResponseRedirects('/access_denied');
    }

    public function testOrgaCanAddLangueWithChosenSource(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $orga = UserFactory::createOne(['roles' => ['ROLE_ORGA']]);
        $personnage = PersonnageFactory::createOne();
        $langue = LangueFactory::createOne();

        $client->loginUser($orga);
        $crawler = $client->request('GET', '/personnage/' . $personnage->getId() . '/updateLangue');
        static::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Ajouter la langue')->form();
        $form->disableValidation();
        $form['form[langue]']->setValue((string) $langue->getId());
        $form['form[source]']->setValue('ORIGINE');
        $client->submit($form);

        static::assertResponseRedirects();

        $em->clear();
        $langues = $em->getRepository(PersonnageLangues::class)->findBy(['personnage' => $personnage->getId()]);
        static::assertCount(1, $langues);
        static::assertSame('ORIGINE', $langues[0]->getSource());
        static::assertNull($langues[0]->getTriggerTag());
    }

    public function testAddLitteratureLangueConsumesTriggerAndStoresTag(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $orga = UserFactory::createOne(['roles' => ['ROLE_ORGA']]);
        $personnage = PersonnageFactory::createOne();
        $langue = LangueFactory::createOne();

        // Le personnage dispose d'un trigger LANGUE COURANTE non consommé
        $personnageEntity = $em->find(Personnage::class, $personnage->getId());
        $trigger = new PersonnageTrigger();
        $trigger->setPersonnage($personnageEntity);
        $trigger->setTag('LANGUE COURANTE');
        $trigger->setDone(false);
        $em->persist($trigger);
        $em->flush();
        $em->clear();

        $client->loginUser($orga);
        $crawler = $client->request('GET', '/personnage/' . $personnage->getId() . '/updateLangue');
        static::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Ajouter la langue')->form();
        $form->disableValidation();
        $form['form[langue]']->setValue((string) $langue->getId());
        $form['form[source]']->setValue('LITTERATURE');
        $form['form[triggerTag]']->setValue('LANGUE COURANTE');
        $client->submit($form);

        static::assertResponseRedirects();

        $em->clear();
        $langues = $em->getRepository(PersonnageLangues::class)->findBy(['personnage' => $personnage->getId()]);
        static::assertCount(1, $langues);
        static::assertSame('LITTERATURE', $langues[0]->getSource());
        static::assertSame('LANGUE COURANTE', $langues[0]->getTriggerTag());

        // Le trigger a été consommé
        $triggers = $em->getRepository(PersonnageTrigger::class)->findBy(['personnage' => $personnage->getId()]);
        static::assertCount(0, $triggers);
    }
}
