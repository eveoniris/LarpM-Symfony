<?php

declare(strict_types=1);

namespace App\Tests\Functional\Depouillement;

use App\Entity\Depouillement;
use App\Entity\User;
use App\Enum\DepouillementType;
use App\Service\DepouillementOrdresService;
use App\Service\DepouillementRetourGroupeService;
use App\Service\DepouillementService;
use App\Service\DepouillementTitresService;
use App\Service\RetourGroupe;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Validation par le wargame des ordres de chaque phase d'un retour de groupe : réservée au wargame et à l'admin, retirée quand le
 * texte de la phase change, signalée à la validation du dossier quand elle manque.
 */
#[Group('functional')]
class DepouillementOrdresTest extends WebTestCase
{
    private function dossier(string $ordres1 = 'Conquérir la côte', ?string $ordres2 = null): Depouillement
    {
        $gn = GnFactory::createOne();
        $groupeGn = GroupeGnFactory::createOne(['groupe' => GroupeFactory::createOne(), 'gn' => $gn]);
        $staff = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_WARGAME']]);
        $dossier = static::getContainer()
            ->get(DepouillementService::class)
            ->ouvrirReel($gn, DepouillementType::GROUPE, (string) $groupeGn->getGroupe()->getNumero(), $staff);
        $retour = new RetourGroupe();
        $retour->ordres1 = $ordres1;
        $retour->ordres2 = $ordres2;
        static::getContainer()->get(DepouillementRetourGroupeService::class)->enregistrer($dossier, $retour, $staff);

        return $dossier;
    }

    private function poster(Depouillement $dossier, User $utilisateur, int $phase, string $decision): void
    {
        $client = static::getClient();
        $client->loginUser($utilisateur);
        $crawler = $client->request('GET', '/gn/' . $dossier->getGn()->getId() . '/depouillement/' . $dossier->getId() . '/etape/retour-groupe');
        $bouton = $crawler->filter('form[action$="/ordres/' . $phase . '/' . $decision . '"]');
        if (0 === $bouton->count()) {
            static::assertResponseStatusCodeSame(200);
            $this->fail('Bouton absent');
        }

        $client->submit($bouton->form());
    }

    private function recharger(Depouillement $dossier): Depouillement
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $recharge = $em->find(Depouillement::class, $dossier->getId());
        static::assertInstanceOf(Depouillement::class, $recharge);

        return $recharge;
    }

    public function testLeWargameValideUnePhaseEtPeutRetirerLaValidation(): void
    {
        static::createClient();
        $dossier = $this->dossier();
        $wargame = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_WARGAME']]);

        $this->poster($dossier, $wargame, 1, 'valider');
        static::assertTrue(static::getContainer()->get(DepouillementOrdresService::class)->phases($this->recharger($dossier))[1]['valide']);

        $this->poster($dossier, $wargame, 1, 'rouvrir');
        static::assertFalse(static::getContainer()->get(DepouillementOrdresService::class)->phases($this->recharger($dossier))[1]['valide']);
    }

    public function testUnJoueurNePeutPasValiderLesOrdres(): void
    {
        $client = static::createClient();
        $dossier = $this->dossier();
        $joueur = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]);

        $client->loginUser($joueur);
        $client->request('POST', '/gn/' . $dossier->getGn()->getId() . '/depouillement/' . $dossier->getId() . '/ordres/1/valider');

        static::assertResponseRedirects('/access_denied');
        static::assertFalse(static::getContainer()->get(DepouillementOrdresService::class)->phases($this->recharger($dossier))[1]['valide']);
    }

    public function testModifierLeTexteDUnePhaseRetireSaValidation(): void
    {
        static::createClient();
        $dossier = $this->dossier('Conquérir la côte', 'Tenir le port');
        $wargame = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_WARGAME']]);
        $ordres = static::getContainer()->get(DepouillementOrdresService::class);
        $ordres->valider($dossier, 1, $wargame);
        $ordres->valider($dossier, 2, $wargame);

        $retour = static::getContainer()->get(DepouillementRetourGroupeService::class)->lire($dossier);
        $retour->ordres2 = 'Tenir le port et la rade';
        static::getContainer()->get(DepouillementRetourGroupeService::class)->enregistrer($dossier, $retour, $wargame);

        $phases = $ordres->phases($this->recharger($dossier));
        static::assertTrue($phases[1]['valide']);
        static::assertFalse($phases[2]['valide']);
    }

    public function testLaValidationDuDossierSignaleLesPhasesNonValidees(): void
    {
        static::createClient();
        $dossier = $this->dossier('Conquérir la côte', 'Tenir le port');
        $wargame = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_WARGAME']]);
        static::getContainer()->get(DepouillementOrdresService::class)->valider($dossier, 1, $wargame);

        $resultat = static::getContainer()->get(DepouillementTitresService::class)->appliquer($this->recharger($dossier));

        static::assertCount(1, $resultat->anomalies);
        static::assertStringContainsString('phase 2', $resultat->anomalies[0]);
    }
}
