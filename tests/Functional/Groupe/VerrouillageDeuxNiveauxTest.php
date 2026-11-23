<?php

declare(strict_types=1);

namespace App\Tests\Functional\Groupe;

use App\Entity\Groupe;
use App\Entity\LogAction;
use App\Enum\LogActionType;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Verrouillage à deux niveaux des groupes : souple (scénariste autorisé, avec alerte et trace) et strict (admins seuls).
 */
#[Group('functional')]
class VerrouillageDeuxNiveauxTest extends WebTestCase
{
    public function testSouplePermetAuScenaristeEtTraceLaModification(): void
    {
        $client = static::createClient();
        $groupe = GroupeFactory::createOne(['lock' => true]);
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_SCENARISTE']]));

        $client->request('POST', '/groupe/' . $groupe->getId() . '/documents');

        // Pas de renvoi vers la fiche du groupe : l'accès est accordé
        static::assertFalse($client->getResponse()->isRedirect('/groupe/' . $groupe->getId()));
        static::assertSame(1, $this->nombreDeTraces());
        $trace = $this->traces()[0];
        static::assertSame($groupe->getId(), $trace->getData()['data']['groupe']);
        static::assertSame('POST', $trace->getData()['data']['methode']);
    }

    public function testSoupleNeTracePasLaSimpleConsultation(): void
    {
        $client = static::createClient();
        $groupe = GroupeFactory::createOne(['lock' => true]);
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_SCENARISTE']]));

        $client->request('GET', '/groupe/' . $groupe->getId() . '/documents');

        static::assertSame(0, $this->nombreDeTraces());
    }

    public function testStrictBloqueLeScenariste(): void
    {
        $client = static::createClient();
        $groupe = GroupeFactory::createOne(['lock' => true]);
        $this->passerEnStrict($groupe->getId());
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_SCENARISTE']]));

        $client->request('POST', '/groupe/' . $groupe->getId() . '/documents');

        static::assertTrue($client->getResponse()->isRedirect('/groupe/' . $groupe->getId()));
        static::assertSame(0, $this->nombreDeTraces());
    }

    public function testAdminNEstJamaisBloqueEtVoitUneInformation(): void
    {
        $client = static::createClient();
        $groupe = GroupeFactory::createOne(['lock' => true]);
        $this->passerEnStrict($groupe->getId());
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]));

        $client->request('GET', '/groupe/' . $groupe->getId() . '/documents');

        static::assertFalse($client->getResponse()->isRedirect('/groupe/' . $groupe->getId()));
        static::assertStringContainsString('Verrouillage strict actif', (string) $client->getResponse()->getContent());
    }

    public function testScenaristeNePeutPasDeverrouillerUnStrict(): void
    {
        $client = static::createClient();
        $groupe = GroupeFactory::createOne(['lock' => true]);
        $this->passerEnStrict($groupe->getId());
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_SCENARISTE']]));

        $client->request('GET', '/groupe/' . $groupe->getId() . '/unlock');

        $frais = $this->groupe($groupe->getId());
        static::assertTrue($frais->getLock());
        static::assertTrue($frais->isLockStrict());
    }

    public function testAdminDeverrouilleUnStrictComplet(): void
    {
        $client = static::createClient();
        $groupe = GroupeFactory::createOne(['lock' => true]);
        $this->passerEnStrict($groupe->getId());
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]));

        $client->request('GET', '/groupe/' . $groupe->getId() . '/unlock');

        $frais = $this->groupe($groupe->getId());
        static::assertFalse($frais->getLock());
        static::assertFalse($frais->isLockStrict());
    }

    private function passerEnStrict(int $groupeId): void
    {
        $groupe = $this->groupe($groupeId);
        $groupe->setLockStrict(true);
        static::getContainer()->get(EntityManagerInterface::class)->flush();
    }

    private function groupe(int $id): Groupe
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        return $em->find(Groupe::class, $id);
    }

    /** @return list<LogAction> */
    private function traces(): array
    {
        return static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(LogAction::class)
            ->findBy(['type' => LogActionType::MODIFICATION_SOUS_VERROU->value]);
    }

    private function nombreDeTraces(): int
    {
        return \count($this->traces());
    }
}
