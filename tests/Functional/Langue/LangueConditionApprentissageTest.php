<?php

declare(strict_types=1);

namespace App\Tests\Functional\Langue;

use App\Entity\GroupeLangue;
use App\Entity\Langue;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * La condition d'apprentissage d'une langue est lisible par les scénaristes,
 * mais modifiable uniquement avec ROLE_REGLE ou ROLE_COHERENCE (ADMIN les hérite).
 */
#[Group('functional')]
class LangueConditionApprentissageTest extends WebTestCase
{
    public function testLeScenaristeLitLaConditionSansPouvoirLaModifier(): void
    {
        $client = static::createClient();
        $langue = $this->creerLangue($client);

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]));

        $crawler = $client->request('GET', '/langue/' . $langue->getId() . '/detail');
        static::assertResponseIsSuccessful();
        static::assertStringContainsString('condition-secrete-xyz', $crawler->text());

        $crawler = $client->request('GET', '/langue/' . $langue->getId() . '/update');
        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('[name="langue[conditionApprentissage]"]'));
    }

    public function testLaRegleLaCoherenceEtLAdminPeuventModifierLaCondition(): void
    {
        $client = static::createClient();
        $langue = $this->creerLangue($client);

        foreach (['ROLE_REGLE', 'ROLE_COHERENCE', 'ROLE_ADMIN'] as $role) {
            $client->loginUser(UserFactory::createOne(['roles' => [$role]]));
            $crawler = $client->request('GET', '/langue/' . $langue->getId() . '/update');
            static::assertResponseIsSuccessful();
            static::assertCount(1, $crawler->filter('[name="langue[conditionApprentissage]"]'), $role);
        }
    }

    public function testListeEtExportsDesPersonnagesParLangue(): void
    {
        $client = static::createClient();
        $langue = $this->creerLangue($client);
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_SCENARISTE', 'ROLE_STATISTIQUE']]));

        $client->request('GET', '/langue/' . $langue->getId() . '/personnages');
        static::assertResponseIsSuccessful();

        $client->request('GET', '/langue/' . $langue->getId() . '/personnages/export');
        static::assertResponseIsSuccessful();
        static::assertStringContainsString('text/csv', (string) $client->getResponse()->headers->get('Content-Type'));

        $client->request('GET', '/langue/' . $langue->getId() . '/personnages/export?format=json');
        static::assertResponseIsSuccessful();
        static::assertJson((string) $client->getResponse()->getContent());
    }

    private function creerLangue(KernelBrowser $client): Langue
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $groupe = $em->getRepository(GroupeLangue::class)->findOneBy([]);
        if (!$groupe instanceof GroupeLangue) {
            $groupe = new GroupeLangue();
            $groupe->setLabel('Groupe test');
            $groupe->setCouleur('#000000');
            $em->persist($groupe);
        }

        $langue = new Langue();
        $langue->setLabel('Langue test condition');
        $langue->setDescription('desc');
        $langue->setConditionApprentissage('condition-secrete-xyz');
        $langue->setGroupeLangue($groupe);
        $em->persist($langue);
        $em->flush();

        return $langue;
    }
}
