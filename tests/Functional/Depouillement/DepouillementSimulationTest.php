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

        $client->request('GET', '/gn/' . $gn->getId() . '/depouillement');
        $client->submitForm('Démarrer une simulation', [
            'depouillement_simulation[type]' => DepouillementType::GROUPE->value,
            'depouillement_simulation[numero]' => '21',
        ]);

        static::assertResponseRedirects();
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
        static::assertSelectorTextContains('body', 'Enveloppe de groupe');
        static::assertSelectorTextContains('body', '21');
    }

    public function testNumeroObligatoire(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]));

        $client->request('GET', '/gn/' . $gn->getId() . '/depouillement');
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

        $crawler = $client->request('GET', '/gn/' . $gn->getId() . '/depouillement');
        $client->submit($crawler->filter('form[action$="/purge"]')->form());

        static::assertResponseRedirects('/gn/' . $gn->getId() . '/depouillement');
        $em->clear();
        static::assertCount(1, $em->getRepository(Depouillement::class)->findBy(['gn' => $gn->getId()]));
        static::assertCount(1, $em->getRepository(Depouillement::class)->findBy(['gn' => $autreGn->getId()]));
        static::assertFalse($em->getRepository(Depouillement::class)->findOneBy(['gn' => $gn->getId()])->isSimulation());
    }

    public function testSuppressionDUneSeuleSimulation(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]));
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $cible = (new Depouillement())
            ->setGn($gn)
            ->setType(DepouillementType::JOUEUR)
            ->setNumero('1')
            ->setSimulation(true);
        $autre = (new Depouillement())
            ->setGn($gn)
            ->setType(DepouillementType::JOUEUR)
            ->setNumero('2')
            ->setSimulation(true);
        $em->persist($cible);
        $em->persist($autre);
        $em->flush();
        $idAutre = $autre->getId();

        $crawler = $client->request('GET', '/gn/' . $gn->getId() . '/depouillement');
        $client->submit($crawler->filter('form[action$="/' . $cible->getId() . '/supprimer"]')->form());

        static::assertResponseRedirects('/gn/' . $gn->getId() . '/depouillement');
        $em->clear();
        $restants = $em->getRepository(Depouillement::class)->findAll();
        static::assertCount(1, $restants);
        static::assertSame($idAutre, $restants[0]->getId());
    }

    public function testSuppressionRefuseSiLeDepouillementEstDUnAutreGn(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $autreGn = GnFactory::createOne();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]));
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $simulation = (new Depouillement())
            ->setGn($autreGn)
            ->setType(DepouillementType::JOUEUR)
            ->setNumero('3')
            ->setSimulation(true);
        $em->persist($simulation);
        $em->flush();

        $client->request('POST', '/gn/' . $gn->getId() . '/depouillement/' . $simulation->getId() . '/supprimer');

        static::assertResponseStatusCodeSame(404);
        $em->clear();
        static::assertCount(1, $em->getRepository(Depouillement::class)->findAll());
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
        $client->request('POST', '/gn/' . $gn->getId() . '/depouillement/purge', ['_token' => 'faux']);

        static::assertResponseRedirects('/gn/' . $gn->getId() . '/depouillement');
        $em->clear();
        static::assertCount(1, $em->getRepository(Depouillement::class)->findBy([], ['id' => 'ASC']));
    }
}
