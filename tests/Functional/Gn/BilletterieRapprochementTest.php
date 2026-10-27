<?php

declare(strict_types=1);

namespace App\Tests\Functional\Gn;

use App\Entity\Billet;
use App\Entity\BilletSync;
use App\Entity\Gn;
use App\Enum\SyncConfiance;
use App\Enum\SyncEtat;
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

    public function testAssociationProduitBilletRecalculeLesPropositions(): void
    {
        [$client, $gn, $billet] = $this->preparer();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $crawler = $client->request('GET', '/gn/' . $gn->getId() . '/billetterie/rapprochement');
        $token = (string) $crawler->filter('form[action$="/rapprochement/produits"] input[name="_token"]')->attr('value');

        $client->request('POST', '/gn/' . $gn->getId() . '/billetterie/rapprochement/produits', [
            '_token' => $token,
            'produit' => ['p1' => (string) $billet->getId(), 'p2' => '0'],
        ]);
        static::assertResponseRedirects();

        $em->clear();
        $billetRecharge = $em->getRepository(Billet::class)->find($billet->getId());
        static::assertSame('p1', $billetRecharge?->getHelloassoProductId());

        $ligne = $em->getRepository(BilletSync::class)->findOneBy(['attendeeId' => 'a1']);
        static::assertSame($billet->getId(), $ligne?->getBillet()?->getId());
        $autre = $em->getRepository(BilletSync::class)->findOneBy(['attendeeId' => 'b1']);
        static::assertNull($autre?->getBillet());
    }

    public function testUnBilletPourDeuxProduitsEstRefuse(): void
    {
        [$client, $gn, $billet] = $this->preparer();

        $crawler = $client->request('GET', '/gn/' . $gn->getId() . '/billetterie/rapprochement');
        $token = (string) $crawler->filter('form[action$="/rapprochement/produits"] input[name="_token"]')->attr('value');

        $client->request('POST', '/gn/' . $gn->getId() . '/billetterie/rapprochement/produits', [
            '_token' => $token,
            'produit' => ['p1' => (string) $billet->getId(), 'p2' => (string) $billet->getId()],
        ]);
        $client->followRedirect();

        static::assertStringContainsString('un seul produit HelloAsso', (string) $client->getInternalResponse()->getContent());
        static::assertNull(static::getContainer()->get(EntityManagerInterface::class)->getRepository(Billet::class)->find($billet->getId())?->getHelloassoProductId());
    }
}
