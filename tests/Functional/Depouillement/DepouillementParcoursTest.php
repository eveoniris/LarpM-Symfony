<?php

declare(strict_types=1);

namespace App\Tests\Functional\Depouillement;

use App\Entity\Depouillement;
use App\Entity\DepouillementHistory;
use App\Entity\Gn;
use App\Entity\User;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Service\DepouillementService;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Parcours de dépouillement : reprise, étapes, remarques, fin de saisie et avertissement groupe en dernier.
 */
// @mago-expect lint:too-many-methods
#[Group('functional')]
class DepouillementParcoursTest extends WebTestCase
{
    private function utilisateur(string $nom): User
    {
        return UserFactory::createOne(['username' => $nom, 'roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]);
    }

    private function demarrer(Gn $gn, DepouillementType $type, string $numero, User $depouilleur): Depouillement
    {
        return static::getContainer()->get(DepouillementService::class)->creerSimulation($gn, $type, $numero, $depouilleur);
    }

    private function url(Depouillement $depouillement, string $suite = ''): string
    {
        return '/gn/' . $depouillement->getGn()->getId() . '/depouillement/' . $depouillement->getId() . $suite;
    }

    private function recharger(int $id): Depouillement
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        return $em->find(Depouillement::class, $id);
    }

    private function soumettreDemarrage(KernelBrowser $client, Gn $gn, DepouillementType $type, string $numero): void
    {
        $client->request('GET', '/gn/' . $gn->getId() . '/depouillement');
        $client->submitForm('Démarrer une simulation', [
            'depouillement_simulation[type]' => $type->value,
            'depouillement_simulation[numero]' => $numero,
        ]);
    }

    public function testMemeDepouilleurRetrouveSonDepouillementSansDoublon(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $moi = $this->utilisateur('moi');
        $existant = $this->demarrer($gn, DepouillementType::JOUEUR, '12', $moi);
        $client->loginUser($moi);

        $this->soumettreDemarrage($client, $gn, DepouillementType::JOUEUR, '12');

        static::assertResponseRedirects($this->url($existant, '/etape/identification'));
        $em = static::getContainer()->get(EntityManagerInterface::class);
        static::assertCount(1, $em->getRepository(Depouillement::class)->findBy([], ['id' => 'ASC']));
    }

    public function testUnAutreDepouilleurEstAvertiPuisPrendLaSuite(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $ancien = $this->utilisateur('ancien');
        $nouveau = $this->utilisateur('nouveau');
        $existant = $this->demarrer($gn, DepouillementType::GROUPE, '21', $ancien);
        $client->loginUser($nouveau);

        $this->soumettreDemarrage($client, $gn, DepouillementType::GROUPE, '21');

        static::assertResponseRedirects($this->url($existant, '/reprise'));
        $crawler = $client->followRedirect();
        static::assertSelectorTextContains('body', 'ancien');
        static::assertSelectorTextContains('body', 'déjà en cours');
        static::assertSame($ancien->getId(), $this->recharger($existant->getId())->getDepouilleur()?->getId());

        $client->submit($crawler->selectButton('Reprendre ce dépouillement')->form());

        static::assertResponseRedirects($this->url($existant, '/etape/identification'));
        $repris = $this->recharger($existant->getId());
        static::assertSame($nouveau->getId(), $repris->getDepouilleur()?->getId());
        static::assertCount(
            1,
            $repris
                ->getHistories()
                ->filter(
                    static fn (DepouillementHistory $h): bool => DepouillementHistory::ACTION_REPRISE === $h->getActionType()
                    && $h->getUser()?->getId() === $nouveau->getId()
                    && str_contains((string) $h->getMotif(), 'ancien'),
                ),
        );
        $em = static::getContainer()->get(EntityManagerInterface::class);
        static::assertCount(1, $em->getRepository(Depouillement::class)->findBy([], ['id' => 'ASC']));
    }

    public function testRemarquesEnregistreesEtTracees(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $moi = $this->utilisateur('moi');
        $depouillement = $this->demarrer($gn, DepouillementType::JOUEUR, '5', $moi);
        $client->loginUser($moi);

        $client->request('GET', $this->url($depouillement, '/etape/remarques'));
        $client->submitForm('Enregistrer et continuer', ['depouillement_remarques[remarques]' => 'Il manque 2 ressources.']);

        static::assertResponseRedirects($this->url($depouillement, '/etape/recapitulatif'));
        $recharge = $this->recharger($depouillement->getId());
        static::assertSame('Il manque 2 ressources.', $recharge->getRemarques());
        static::assertCount(1, $recharge->getHistories()->filter(static fn (DepouillementHistory $h): bool => DepouillementHistory::ACTION_MISE_A_JOUR === $h->getActionType()));
    }

    public function testTerminerPasseEnAValiderEtVerrouilleLaSaisie(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $moi = $this->utilisateur('moi');
        $depouillement = $this->demarrer($gn, DepouillementType::JOUEUR, '5', $moi);
        $client->loginUser($moi);

        $crawler = $client->request('GET', $this->url($depouillement, '/etape/recapitulatif'));
        $client->submit($crawler->selectButton('Terminer le dépouillement')->form());

        static::assertResponseRedirects($this->url($depouillement, '/etape/recapitulatif'));
        static::assertSame(DepouillementStatut::A_VALIDER, $this->recharger($depouillement->getId())->getStatut());

        $client->followRedirect();
        static::assertSelectorNotExists('button:contains("Terminer le dépouillement")');
        $client->request('GET', $this->url($depouillement, '/etape/remarques'));
        static::assertSelectorNotExists('form[name="depouillement_remarques"]');

        // Retrouver cette enveloppe l'ouvre en lecture seule (récapitulatif), sans nouveau dépouillement.
        $this->soumettreDemarrage($client, $gn, DepouillementType::JOUEUR, '5');
        static::assertResponseRedirects($this->url($depouillement, '/etape/recapitulatif'));
    }

    public function testEnveloppeDeGroupeAvertieTantQueDesEnveloppesJoueursSontEnCours(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $moi = $this->utilisateur('moi');
        $joueur = $this->demarrer($gn, DepouillementType::JOUEUR, '1', $moi);
        $groupe = $this->demarrer($gn, DepouillementType::GROUPE, '21', $moi);
        $client->loginUser($moi);

        $client->request('GET', $this->url($groupe, '/etape/identification'));
        static::assertSelectorTextContains('body', 'enveloppe(s) de joueur sont encore en cours');

        $service = static::getContainer()->get(DepouillementService::class);
        $service->terminerSaisie($joueur, $moi);

        $client->request('GET', $this->url($groupe, '/etape/identification'));
        static::assertSelectorTextNotContains('body', 'enveloppe(s) de joueur sont encore en cours');
    }

    public function testEtapeInconnueEtAutreGnDonnentUneErreur404(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $autreGn = GnFactory::createOne();
        $moi = $this->utilisateur('moi');
        $depouillement = $this->demarrer($gn, DepouillementType::JOUEUR, '5', $moi);
        $client->loginUser($moi);

        $client->request('GET', $this->url($depouillement, '/etape/inexistante'));
        static::assertResponseStatusCodeSame(404);

        $client->request('GET', '/gn/' . $autreGn->getId() . '/depouillement/' . $depouillement->getId() . '/etape/identification');
        static::assertResponseStatusCodeSame(404);
    }

    public function testToutesLesEtapesSAffichent(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $moi = $this->utilisateur('moi');
        $depouillement = $this->demarrer($gn, DepouillementType::JOUEUR, '5', $moi);
        $client->loginUser($moi);

        foreach (['identification', 'photos', 'contenu', 'remarques', 'recapitulatif'] as $etape) {
            $client->request('GET', $this->url($depouillement, '/etape/' . $etape));
            static::assertResponseIsSuccessful($etape);
        }
    }
}
