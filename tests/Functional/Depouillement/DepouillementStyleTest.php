<?php

declare(strict_types=1);

namespace App\Tests\Functional\Depouillement;

use App\Entity\Depouillement;
use App\Entity\User;
use App\Enum\DepouillementType;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Style : avec l'interface bêta (lm-design) les écrans utilisent les composants lm-* (verre poli, onglets, tableaux) ;
 * sans elle ils gardent le rendu Bootstrap, strictement inchangé.
 */
#[Group('functional')]
class DepouillementStyleTest extends WebTestCase
{
    private function utilisateur(bool $uiBeta): User
    {
        return UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST'], 'uiBeta' => $uiBeta]);
    }

    private function enveloppeGroupeAvecReliquat(User $depouilleur): Depouillement
    {
        $groupeGn = GroupeGnFactory::createOne();
        $depouillement = (new Depouillement())
            ->setGn($groupeGn->getGn())
            ->setType(DepouillementType::GROUPE)
            ->setNumero((string) $groupeGn->getGroupe()->getNumero())
            ->setGroupeGn($groupeGn)
            ->setSimulation(true)
            ->setDepouilleur($depouilleur)
            ->setDonnees(['contenu_groupe' => ['compteurs' => ['bois' => 5]]]);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($depouillement);
        $em->flush();

        return $depouillement;
    }

    private function etape(Depouillement $depouillement, string $etape): string
    {
        return '/gn/' . $depouillement->getGn()->getId() . '/depouillement/' . $depouillement->getId() . '/etape/' . $etape;
    }

    public function testAvecLInterfaceBetaLesEcransUtilisentLeStyleLmDesign(): void
    {
        $client = static::createClient();
        $moi = $this->utilisateur(true);
        $depouillement = $this->enveloppeGroupeAvecReliquat($moi);
        $client->loginUser($moi);

        $client->request('GET', $this->etape($depouillement, 'contenu'));

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('header.lm-page-head h1.lm-page-title');
        static::assertSelectorExists('nav.lm-tabs a.lm-tab[aria-current="page"]');
        static::assertSelectorNotExists('ul.nav-pills');
        static::assertSelectorExists('.lm-carte-verre');
        // Le tableau du reliquat est dans un gabarit inclus : il reçoit lui aussi le style.
        static::assertSelectorExists('.lm-table-wrap table.lm-table');

        $client->request('GET', '/gn/' . $depouillement->getGn()->getId() . '/depouillement');
        static::assertSelectorExists('header.lm-page-head');
        static::assertSelectorExists('.lm-table-wrap table.lm-table');
    }

    public function testSansLInterfaceBetaLeRenduBootstrapEstConserve(): void
    {
        $client = static::createClient();
        $moi = $this->utilisateur(false);
        $depouillement = $this->enveloppeGroupeAvecReliquat($moi);
        $client->loginUser($moi);

        $client->request('GET', $this->etape($depouillement, 'contenu'));

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('ul.nav-pills');
        static::assertSelectorNotExists('nav.lm-tabs');
        static::assertSelectorExists('.table-responsive table.table');
        static::assertSelectorNotExists('.lm-table-wrap');
        static::assertSelectorNotExists('table.lm-table');
    }
}
