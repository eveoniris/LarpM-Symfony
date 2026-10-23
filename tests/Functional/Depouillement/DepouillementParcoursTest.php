<?php

declare(strict_types=1);

namespace App\Tests\Functional\Depouillement;

use App\Entity\Depouillement;
use App\Entity\DepouillementHistory;
use App\Entity\Gn;
use App\Entity\GroupeGn;
use App\Entity\User;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Service\DepouillementService;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Parcours de dépouillement : le numéro saisi (groupe ou personnage) est croisé avec le GN pour retrouver la session
 * de groupe ou le participant. Reprise, étapes, remarques, fin de saisie et avertissement groupe en dernier.
 */
// @mago-expect lint:too-many-methods
#[Group('functional')]
class DepouillementParcoursTest extends WebTestCase
{
    private function utilisateur(string $nom): User
    {
        return UserFactory::createOne(['username' => $nom, 'roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]);
    }

    private function groupeGn(Gn $gn): GroupeGn
    {
        return GroupeGnFactory::createOne(['gn' => $gn]);
    }

    /**
     * Inscrit un personnage au GN et retourne son numéro (son identifiant, imprimé sur l'enveloppe).
     */
    private function numeroPersonnage(Gn $gn, ?GroupeGn $groupeGn = null): string
    {
        $personnage = PersonnageFactory::createOne();
        ParticipantFactory::createOne([
            'gn' => $gn,
            'user' => UserFactory::createOne(),
            'personnage' => $personnage,
            'groupeGn' => $groupeGn,
        ]);

        return (string) $personnage->getId();
    }

    private function demarrer(Gn $gn, DepouillementType $type, User $depouilleur, ?GroupeGn $groupeGn = null): Depouillement
    {
        $numero = DepouillementType::GROUPE === $type
            ? (string) ($groupeGn ?? $this->groupeGn($gn))->getGroupe()->getNumero()
            : $this->numeroPersonnage($gn, $groupeGn);

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

    public function testLeNumeroDeGroupeRetrouveLaSessionDuGroupeDeCeGn(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $groupeGn = $this->groupeGn($gn);
        $autreGn = GnFactory::createOne();
        GroupeGnFactory::createOne(['gn' => $autreGn, 'groupe' => $groupeGn->getGroupe()]);
        $client->loginUser($this->utilisateur('moi'));

        $this->soumettreDemarrage($client, $gn, DepouillementType::GROUPE, (string) $groupeGn->getGroupe()->getNumero());

        static::assertResponseRedirects();
        $depouillement = static::getContainer()->get(EntityManagerInterface::class)->getRepository(Depouillement::class)->findBy([], ['id' => 'ASC'])[0];
        static::assertSame($groupeGn->getId(), $depouillement->getGroupeGn()?->getId());
        static::assertNull($depouillement->getParticipant());
        static::assertSame($gn->getId(), $depouillement->getGn()->getId());
    }

    public function testLeNumeroDePersonnageRetrouveLeParticipantDeCeGn(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $groupeGn = $this->groupeGn($gn);
        $numero = $this->numeroPersonnage($gn, $groupeGn);
        $client->loginUser($this->utilisateur('moi'));

        $this->soumettreDemarrage($client, $gn, DepouillementType::JOUEUR, $numero);

        static::assertResponseRedirects();
        $depouillement = static::getContainer()->get(EntityManagerInterface::class)->getRepository(Depouillement::class)->findBy([], ['id' => 'ASC'])[0];
        static::assertSame((int) $numero, $depouillement->getParticipant()?->getPersonnage()?->getId());
        static::assertSame($groupeGn->getId(), $depouillement->getParticipant()?->getGroupeGn()?->getId());
        static::assertNull($depouillement->getGroupeGn());
    }

    public function testNumeroSaisiAvecZerosOuLibelleEstNormalise(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $numero = $this->numeroPersonnage($gn);
        $client->loginUser($this->utilisateur('moi'));

        $this->soumettreDemarrage($client, $gn, DepouillementType::JOUEUR, \sprintf('%05d Conan', $numero));

        static::assertResponseRedirects();
        $depouillement = static::getContainer()->get(EntityManagerInterface::class)->getRepository(Depouillement::class)->findBy([], ['id' => 'ASC'])[0];
        static::assertSame($numero, $depouillement->getNumero());
    }

    public function testNumeroInconnuDansCeGnEstRefuseAvecUnMessage(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $autreGn = GnFactory::createOne();
        $numeroAilleurs = $this->numeroPersonnage($autreGn);
        $client->loginUser($this->utilisateur('moi'));

        $this->soumettreDemarrage($client, $gn, DepouillementType::JOUEUR, $numeroAilleurs);
        static::assertSelectorTextContains('body', 'Aucun personnage n°' . $numeroAilleurs . ' inscrit à ce GN');

        $this->soumettreDemarrage($client, $gn, DepouillementType::GROUPE, '987654');
        static::assertSelectorTextContains('body', 'Aucun groupe n°987654 dans ce GN');

        $this->soumettreDemarrage($client, $gn, DepouillementType::GROUPE, 'abc');
        static::assertSelectorTextContains('body', 'doit commencer par des chiffres');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        static::assertCount(0, $em->getRepository(Depouillement::class)->findBy([], ['id' => 'ASC']));
    }

    public function testMemeDepouilleurRetrouveSonDepouillementSansDoublon(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $moi = $this->utilisateur('moi');
        $existant = $this->demarrer($gn, DepouillementType::JOUEUR, $moi);
        $client->loginUser($moi);

        $this->soumettreDemarrage($client, $gn, DepouillementType::JOUEUR, $existant->getNumero());

        static::assertResponseRedirects($this->url($existant, '/etape/identification'));
        $em = static::getContainer()->get(EntityManagerInterface::class);
        static::assertCount(1, $em->getRepository(Depouillement::class)->findBy([], ['id' => 'ASC']));

        $client->followRedirect();
        static::assertSelectorTextContains('body', 'vous reprenez le dépouillement existant, rien n\'a été créé ni écrasé');
    }

    public function testLeContenuEnregistreEstReaffichePourLaReprise(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $moi = $this->utilisateur('moi');
        $existant = $this->demarrer($gn, DepouillementType::JOUEUR, $moi);
        $client->loginUser($moi);

        $crawler = $client->request('GET', $this->url($existant, '/etape/contenu'));
        $client->submit($crawler->selectButton('Enregistrer le contenu')->form(['contenu_joueur[pincesALinge]' => '4', 'contenu_joueur[surplus][bois]' => '7']));

        // En revenant avec le même numéro, la saisie est préremplie, pas vierge.
        $this->soumettreDemarrage($client, $gn, DepouillementType::JOUEUR, $existant->getNumero());
        $crawler = $client->request('GET', $this->url($existant, '/etape/contenu'));
        $valeurs = $crawler->selectButton('Enregistrer le contenu')->form()->getValues();
        static::assertSame('4', $valeurs['contenu_joueur[pincesALinge]']);
        static::assertSame('7', $valeurs['contenu_joueur[surplus][bois]']);
    }

    public function testUnAutreDepouilleurEstAvertiPuisPrendLaSuite(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $ancien = $this->utilisateur('ancien');
        $nouveau = $this->utilisateur('nouveau');
        $existant = $this->demarrer($gn, DepouillementType::GROUPE, $ancien);
        $client->loginUser($nouveau);

        $this->soumettreDemarrage($client, $gn, DepouillementType::GROUPE, $existant->getNumero());

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
        $depouillement = $this->demarrer($gn, DepouillementType::JOUEUR, $moi);
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
        $depouillement = $this->demarrer($gn, DepouillementType::JOUEUR, $moi);
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
        $this->soumettreDemarrage($client, $gn, DepouillementType::JOUEUR, $depouillement->getNumero());
        static::assertResponseRedirects($this->url($depouillement, '/etape/recapitulatif'));
    }

    public function testEnveloppeDeGroupeAvertitSeulementPourLesJoueursDeCeGroupe(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $moi = $this->utilisateur('moi');
        $groupeGn = $this->groupeGn($gn);
        $autreGroupeGn = $this->groupeGn($gn);
        $joueur = $this->demarrer($gn, DepouillementType::JOUEUR, $moi, $groupeGn);
        $this->demarrer($gn, DepouillementType::JOUEUR, $moi, $autreGroupeGn);
        $groupe = $this->demarrer($gn, DepouillementType::GROUPE, $moi, $groupeGn);
        $groupeSansJoueur = $this->demarrer($gn, DepouillementType::GROUPE, $moi, $this->groupeGn($gn));
        $client->loginUser($moi);

        $client->request('GET', $this->url($groupe, '/etape/identification'));
        static::assertSelectorTextContains('body', '1 enveloppe(s) de joueur sont encore en cours');

        $client->request('GET', $this->url($groupeSansJoueur, '/etape/identification'));
        static::assertSelectorTextNotContains('body', 'enveloppe(s) de joueur sont encore en cours');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        static::getContainer()->get(DepouillementService::class)->terminerSaisie($em->find(Depouillement::class, $joueur->getId()), $em->find(User::class, $moi->getId()));

        $client->request('GET', $this->url($groupe, '/etape/identification'));
        static::assertSelectorTextNotContains('body', 'enveloppe(s) de joueur sont encore en cours');
    }

    public function testEtapeInconnueEtAutreGnDonnentUneErreur404(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $autreGn = GnFactory::createOne();
        $moi = $this->utilisateur('moi');
        $depouillement = $this->demarrer($gn, DepouillementType::JOUEUR, $moi);
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
        $depouillement = $this->demarrer($gn, DepouillementType::JOUEUR, $moi);
        $client->loginUser($moi);

        foreach (['identification', 'photos', 'etiquettes', 'contenu', 'actions', 'conceptions', 'remarques', 'recapitulatif'] as $etape) {
            $client->request('GET', $this->url($depouillement, '/etape/' . $etape));
            static::assertResponseIsSuccessful($etape);
        }
    }

    public function testLEnveloppeDeGroupeNAPasLesEtapesActionsEtConceptions(): void
    {
        $client = static::createClient();
        $gn = GnFactory::createOne();
        $moi = $this->utilisateur('moi');
        $depouillement = $this->demarrer($gn, DepouillementType::GROUPE, $moi);
        $client->loginUser($moi);

        foreach (['identification', 'photos', 'etiquettes', 'contenu', 'remarques', 'recapitulatif'] as $etape) {
            $client->request('GET', $this->url($depouillement, '/etape/' . $etape));
            static::assertResponseIsSuccessful($etape);
        }

        foreach (['actions', 'conceptions'] as $etape) {
            $client->request('GET', $this->url($depouillement, '/etape/' . $etape));
            static::assertResponseStatusCodeSame(404, $etape);
        }
    }
}
