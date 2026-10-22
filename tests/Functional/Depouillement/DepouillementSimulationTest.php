<?php

declare(strict_types=1);

namespace App\Tests\Functional\Depouillement;

use App\Entity\Depouillement;
use App\Enum\DepouillementType;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Mode simulation : création depuis la page du GN et purge, sans jamais toucher aux dépouillements réels.
 */
#[Group('functional')]
class DepouillementSimulationTest extends WebTestCase
{
    public function testCreationDUneSimulation(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $beta = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]);
        $client->loginUser($beta);

        $client->request('GET', '/depouillement/gn/' . $gn->getId());
        $client->submitForm('Démarrer une simulation', [
            'depouillement_simulation[type]' => DepouillementType::GROUPE->value,
            'depouillement_simulation[numero]' => '21',
        ]);

        static::assertResponseRedirects('/depouillement/gn/' . $gn->getId());
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $depouillements = $em->getRepository(Depouillement::class)->findBy([], ['id' => 'ASC']);
        static::assertCount(1, $depouillements);
        static::assertTrue($depouillements[0]->isSimulation());
        static::assertSame('21', $depouillements[0]->getNumero());
        static::assertSame(DepouillementType::GROUPE, $depouillements[0]->getType());
        static::assertSame($beta->getId(), $depouillements[0]->getDepouilleur()?->getId());
        static::assertCount(1, $depouillements[0]->getHistories());

        $client->followRedirect();
        static::assertSelectorTextContains('table', '21');
    }

    public function testNumeroObligatoire(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]));

        $client->request('GET', '/depouillement/gn/' . $gn->getId());
        $client->submitForm('Démarrer une simulation', ['depouillement_simulation[numero]' => '']);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        static::assertCount(0, $em->getRepository(Depouillement::class)->findBy([], ['id' => 'ASC']));
    }

    public function testPurgeSupprimeLesSimulationsDuGnSeulement(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $autreGn = GnFactory::createOne();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]));
        $em = static::getContainer()->get(EntityManagerInterface::class);

        foreach ([[$gn, true], [$gn, false], [$autreGn, true]] as [$cible, $simulation]) {
            $em->persist(
                (new Depouillement())
                    ->setGn($cible)
                    ->setType(DepouillementType::JOUEUR)
                    ->setNumero('1')
                    ->setSimulation($simulation),
            );
        }
        $em->flush();

        $crawler = $client->request('GET', '/depouillement/gn/' . $gn->getId());
        $client->submit($crawler->filter('form[action$="/purge"]')->form());

        static::assertResponseRedirects('/depouillement/gn/' . $gn->getId());
        $em->clear();
        static::assertCount(1, $em->getRepository(Depouillement::class)->findBy(['gn' => $gn->getId()]));
        static::assertCount(1, $em->getRepository(Depouillement::class)->findBy(['gn' => $autreGn->getId()]));
        static::assertFalse($em->getRepository(Depouillement::class)->findOneBy(['gn' => $gn->getId()])->isSimulation());
    }

    public function testPurgeRefuseSansJetonValide(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]));
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist(
            (new Depouillement())
                ->setGn($gn)
                ->setType(DepouillementType::JOUEUR)
                ->setNumero('1')
                ->setSimulation(true),
        );
        $em->flush();

        // @mago-expect lint:no-literal-password
        $client->request('POST', '/depouillement/gn/' . $gn->getId() . '/purge', ['_token' => 'faux']);

        static::assertResponseRedirects('/depouillement/gn/' . $gn->getId());
        $em->clear();
        static::assertCount(1, $em->getRepository(Depouillement::class)->findBy([], ['id' => 'ASC']));
    }
}
