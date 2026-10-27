<?php

declare(strict_types=1);

namespace App\Tests\Functional\Gn;

use App\Entity\Billet;
use App\Entity\BilletSync;
use App\Entity\BilletTarif;
use App\Entity\Gn;
use App\Entity\User;
use App\Enum\SyncConfiance;
use App\Enum\SyncEtat;
use App\Repository\BilletTarifRepository;
use App\Service\HelloAsso\BilletSyncService;
use App\Tests\Factory\BilletFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tableau de rapprochement HelloAsso : compteurs, filtres, pagination et association produit -> billet.
 */
#[Group('functional')]
class BilletterieRapprochementTest extends WebTestCase
{
    private function ligne(Gn $gn, string $id, string $productId, SyncConfiance $confiance, string $status = 'enabled'): BilletSync
    {
        $sync = new BilletSync();
        $sync->setGn($gn);
        $sync->setAttendeeId($id);
        $sync->setProductId($productId);
        $sync->setEmail($id . '@example.org');
        $sync->setNom('Joueur ' . $id);
        $sync->setStatus($status);
        $sync->setPrice(1000);
        $sync->setConfiance($confiance);
        $sync->setEtat(SyncEtat::A_VALIDER);
        $sync->setSynchroniseLe(new DateTime());

        return $sync;
    }

    /** @return array{0: KernelBrowser, 1: Gn, 2: Billet} */
    private function preparer(): array
    {
        $_SERVER['HELLOASSO_PLUS_ENABLED'] = $_ENV['HELLOASSO_PLUS_ENABLED'] = '1';
        putenv('HELLOASSO_PLUS_ENABLED=1');
        $client = static::createClient();
        $gn = GnFactory::createOne(['helloassoEventId' => 'evt1']);
        $billet = BilletFactory::createOne(['gn' => $gn, 'label' => 'Billet adulte']);
        $admin = UserFactory::createOne(['roles' => ['ROLE_SUPER_ADMIN']]);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        for ($i = 1; $i <= 60; ++$i) {
            $em->persist($this->ligne($gn, 'a' . $i, 'p1', SyncConfiance::PROBABLE));
        }
        $em->persist($this->ligne($gn, 'b1', 'p2', SyncConfiance::AUCUN, 'refunded'));
        $em->flush();

        $client->loginUser($admin);

        return [$client, $gn, $billet];
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HELLOASSO_PLUS_ENABLED'], $_ENV['HELLOASSO_PLUS_ENABLED']);
        putenv('HELLOASSO_PLUS_ENABLED=0');
        parent::tearDown();
    }

    public function testCompteursEtPaginationPar50(): void
    {
        [$client, $gn] = $this->preparer();

        $crawler = $client->request('GET', '/gn/' . $gn->getId() . '/billetterie/rapprochement');
        static::assertResponseIsSuccessful();
        $html = (string) $client->getInternalResponse()->getContent();

        static::assertStringContainsString('À valider <span class="badge bg-secondary">61</span>', $html);
        static::assertStringContainsString('Tous <span class="badge bg-secondary">61</span>', $html);
        // 50 lignes par page, la 2e page contient le reste.
        static::assertCount(50, $crawler->filter('tbody tr')->reduce(static fn ($n): bool => str_contains($n->text(), 'Joueur')));
        static::assertStringContainsString('page=2', $html);

        $client->request('GET', '/gn/' . $gn->getId() . '/billetterie/rapprochement?page=2');
        static::assertResponseIsSuccessful();
        static::assertSame(11, substr_count((string) $client->getInternalResponse()->getContent(), 'Joueur '));
    }

    public function testFiltreParStatut(): void
    {
        [$client, $gn] = $this->preparer();

        $client->request('GET', '/gn/' . $gn->getId() . '/billetterie/rapprochement?status=refunded');
        static::assertResponseIsSuccessful();
        $html = (string) $client->getInternalResponse()->getContent();

        static::assertStringContainsString('Joueur b1', $html);
        static::assertStringNotContainsString('Joueur a1', $html);
    }

    private function postTarifs(KernelBrowser $client, Gn $gn, array $tarifs): void
    {
        $crawler = $client->request('GET', '/gn/' . $gn->getId() . '/billetterie/rapprochement');
        $token = (string) $crawler->filter('form[action$="/rapprochement/produits"] input[name="_token"]')->attr('value');

        $client->request('POST', '/gn/' . $gn->getId() . '/billetterie/rapprochement/produits', ['_token' => $token, 'tarif' => $tarifs]);
    }

    public function testAssociationTarifBilletRecalculeLesPropositions(): void
    {
        [$client, $gn, $billet] = $this->preparer();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $this->postTarifs($client, $gn, [
            ['product' => 'p1', 'price' => '1000', 'billet' => (string) $billet->getId()],
            ['product' => 'p2', 'price' => '1000', 'billet' => '0'],
        ]);
        static::assertResponseRedirects();

        $em->clear();
        $ligne = $em->getRepository(BilletSync::class)->findOneBy(['attendeeId' => 'a1']);
        static::assertSame($billet->getId(), $ligne?->getBillet()?->getId());
        $autre = $em->getRepository(BilletSync::class)->findOneBy(['attendeeId' => 'b1']);
        static::assertNull($autre?->getBillet());
    }

    public function testPlusieursProduitsPeuventMenerAuMemeBillet(): void
    {
        [$client, $gn, $billet] = $this->preparer();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $this->postTarifs($client, $gn, [
            ['product' => 'p1', 'price' => '1000', 'billet' => (string) $billet->getId()],
            ['product' => 'p2', 'price' => '1000', 'billet' => (string) $billet->getId()],
        ]);
        $client->followRedirect();

        static::assertStringNotContainsString('deux fois', (string) $client->getInternalResponse()->getContent());
        $em->clear();
        static::assertSame($billet->getId(), $em->getRepository(BilletSync::class)->findOneBy(['attendeeId' => 'b1'])?->getBillet()?->getId());
    }

    public function testUnMemeCoupleProduitPrixEnDoubleEstRefuse(): void
    {
        [$client, $gn, $billet] = $this->preparer();

        $this->postTarifs($client, $gn, [
            ['product' => 'p1', 'price' => '1000', 'billet' => (string) $billet->getId()],
            ['product' => 'p1', 'price' => '1000', 'billet' => (string) $billet->getId()],
        ]);
        $client->followRedirect();

        static::assertStringContainsString('deux fois au même prix', (string) $client->getInternalResponse()->getContent());
    }

    public function testBilletPrioritaireTrancheEtDoublonsSansPrioritaireSontEcartes(): void
    {
        [$client, $gn, $billetPj] = $this->preparer();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $billetSolidaire = BilletFactory::createOne(['gn' => $gn, 'label' => 'PJ solidaire']);
        $solidaire = UserFactory::createOne(['email' => 'solidaire@example.org']);
        $doublon = UserFactory::createOne(['email' => 'doublon@example.org']);

        $lignes = [];
        foreach ([
            ['s1', 'p1', 1000, $solidaire],
            ['s2', 'p3', 500,  $solidaire],
            ['d1', 'p1', 1000, $doublon],
            ['d2', 'p2', 1000, $doublon],
        ] as [$id, $produit, $prix, $user]) {
            $ligne = $this->ligne($gn, $id, $produit, SyncConfiance::SUR);
            $ligne->setPrice($prix);
            $ligne->setUser($user);
            $em->persist($ligne);
            $lignes[$id] = $ligne;
        }
        $em->flush();

        $this->postTarifs($client, $gn, [
            ['product' => 'p1', 'price' => '1000', 'billet' => (string) $billetPj->getId()],
            ['product' => 'p2', 'price' => '1000', 'billet' => (string) $billetPj->getId()],
            ['product' => 'p3', 'price' => '500', 'billet' => (string) $billetSolidaire->getId(), 'prioritaire' => '1'],
        ]);

        $result = static::getContainer()->get(BilletSyncService::class)->validateAllSure($gn, null);

        $em->clear();
        static::assertSame(['valides' => 2, 'doublons' => 2], $result);
        static::assertSame(SyncEtat::RAPPROCHE, $em->getRepository(BilletSync::class)->findOneBy(['attendeeId' => 's1'])?->getEtat());
        static::assertSame(SyncEtat::RAPPROCHE, $em->getRepository(BilletSync::class)->findOneBy(['attendeeId' => 's2'])?->getEtat());
        static::assertSame(SyncEtat::A_VALIDER, $em->getRepository(BilletSync::class)->findOneBy(['attendeeId' => 'd1'])?->getEtat());

        $participant = $em->getRepository(User::class)->find($solidaire->getId())?->getParticipant($em->getRepository(Gn::class)->find($gn->getId()));
        static::assertSame($billetSolidaire->getId(), $participant?->getBillet()?->getId());
    }

    public function testResolutionDuTarifParPrix(): void
    {
        $gn = new Gn();
        $pj90 = (new BilletTarif())
            ->setProductId('pj')
            ->setPrice(9000)
            ->setBillet((new Billet())->setLabel('PJ 90'));
        $pj105 = (new BilletTarif())
            ->setProductId('pj')
            ->setPrice(10500)
            ->setBillet((new Billet())->setLabel('PJ 105'));
        $tous = (new BilletTarif())
            ->setProductId('don')
            ->setPrice(null)
            ->setBillet((new Billet())->setLabel('Solidaire'));
        $tarifs = [$pj90, $pj105, $tous];

        static::assertSame($pj105, BilletTarifRepository::resolve($tarifs, 'pj', 10500));
        static::assertSame($pj90, BilletTarifRepository::resolve($tarifs, 'pj', 9000));
        static::assertNull(BilletTarifRepository::resolve($tarifs, 'pj', 7000));
        static::assertSame($tous, BilletTarifRepository::resolve($tarifs, 'don', 2500));
        static::assertNull(BilletTarifRepository::resolve($tarifs, null, 9000));
        static::assertNotNull($gn);
    }
}
