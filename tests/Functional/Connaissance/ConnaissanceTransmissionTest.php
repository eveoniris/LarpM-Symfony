<?php

declare(strict_types=1);

namespace App\Tests\Functional\Connaissance;

use App\Entity\Connaissance;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * La transmission d'une connaissance est lisible par les scénaristes,
 * mais modifiable uniquement avec ROLE_REGLE (ADMIN en hérite).
 */
#[Group('functional')]
class ConnaissanceTransmissionTest extends WebTestCase
{
    public function testLeScenaristeLitLaTransmissionSansPouvoirLaModifier(): void
    {
        $client = static::createClient();
        $connaissance = $this->creerConnaissance($client);

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_SCENARISTE']]));

        $crawler = $client->request('GET', '/connaissance/' . $connaissance->getId());
        static::assertResponseIsSuccessful();
        static::assertStringContainsString('transmission-secrete-xyz', $crawler->text());

        $crawler = $client->request('GET', '/connaissance/' . $connaissance->getId() . '/update');
        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('[name="connaissance[transmission]"]'));
    }

    public function testLaRegleEtLAdminPeuventModifierLaTransmission(): void
    {
        $client = static::createClient();
        $connaissance = $this->creerConnaissance($client);

        foreach (['ROLE_REGLE', 'ROLE_ADMIN'] as $role) {
            $client->loginUser(UserFactory::createOne(['roles' => [$role]]));
            $crawler = $client->request('GET', '/connaissance/' . $connaissance->getId() . '/update');
            static::assertResponseIsSuccessful();
            static::assertCount(1, $crawler->filter('[name="connaissance[transmission]"]'), $role);
        }
    }

    private function creerConnaissance(KernelBrowser $client): Connaissance
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $connaissance = new Connaissance();
        $connaissance->setLabel('Test transmission');
        $connaissance->setDescription('desc');
        $connaissance->setTransmission('transmission-secrete-xyz');
        $connaissance->setNiveau(1);
        $connaissance->setDocumentUrl('');
        $em->persist($connaissance);
        $em->flush();

        return $connaissance;
    }
}
