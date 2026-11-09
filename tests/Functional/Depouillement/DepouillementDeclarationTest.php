<?php

declare(strict_types=1);

namespace App\Tests\Functional\Depouillement;

use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Entity\User;
use App\Enum\DepouillementType;
use App\Service\DecisionsDeclaration;
use App\Service\DepouillementContenuService;
use App\Service\DepouillementDeclarationService;
use App\Service\DepouillementService;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Déclaration du joueur : étape propre, texte libre et apprentissages, provenance de chaque ligne,
 * et contenu physique de l'enveloppe fermé au joueur.
 */
#[Group('functional')]
// @mago-expect lint:too-many-methods
class DepouillementDeclarationTest extends WebTestCase
{
    /**
     * @return array{0: Gn, 1: User, 2: Depouillement}
     */
    private function dossierDeJoueur(): array
    {
        $gn = GnFactory::createOne();
        $user = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]);
        $personnage = PersonnageFactory::createOne(['user' => $user]);
        $groupeGn = GroupeGnFactory::createOne(['groupe' => GroupeFactory::createOne(), 'gn' => $gn]);
        ParticipantFactory::createOne(['user' => $user, 'gn' => $gn, 'groupeGn' => $groupeGn, 'personnage' => $personnage]);

        $dossier = static::getContainer()
            ->get(DepouillementService::class)
            ->ouvrirReel($gn, DepouillementType::JOUEUR, (string) $personnage->getId(), $user);

        return [$gn, $user, $dossier];
    }

    private function url(Gn $gn, Depouillement $dossier, string $suite): string
    {
        return '/gn/' . $gn->getId() . '/depouillement/' . $dossier->getId() . '/' . $suite;
    }

    private function recharger(Depouillement $dossier): Depouillement
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $recharge = $em->find(Depouillement::class, $dossier->getId());
        static::assertInstanceOf(Depouillement::class, $recharge);

        return $recharge;
    }

    /**
     * @return array{source: string, etat: string}
     */
    private function decision(Depouillement $dossier, string $champ): array
    {
        return DecisionsDeclaration::lire(DecisionsDeclaration::depuis($this->recharger($dossier)->getDonnees()), $champ);
    }

    public function testLeJoueurEntreParLaDeclarationEtNeVoitPasLeContenuPhysique(): void
    {
        $client = static::createClient();
        [$gn, $user] = $this->dossierDeJoueur();
        $client->loginUser($user);

        $client->request('GET', '/gn/' . $gn->getId() . '/mon-retour');
        static::assertResponseRedirects();
        $client->followRedirect();

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('form[name="contenu_joueur"]');
        static::assertSelectorExists('form[name="declaration_joueur"]');
        static::assertSelectorNotExists('input[name="contenu_joueur[fichePersonnage]"]');
        static::assertSelectorNotExists('input[name="contenu_joueur[ivoireDecision]"]');
        static::assertSelectorTextContains('body', 'Déclaration du joueur');
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function etapesFermees(): iterable
    {
        foreach (['identification', 'photos', 'etiquettes', 'contenu', 'actions'] as $etape) {
            yield $etape => [$etape];
        }
    }

    #[DataProvider('etapesFermees')]
    public function testLesEtapesDuContenuPhysiqueSontIntrouvablesPourLeJoueur(string $etape): void
    {
        $client = static::createClient();
        [$gn, $user, $dossier] = $this->dossierDeJoueur();
        $client->loginUser($user);

        $client->request('GET', $this->url($gn, $dossier, 'etape/' . $etape));

        static::assertResponseStatusCodeSame(404);
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function ecrituresFermees(): iterable
    {
        yield 'portion actions' => ['contenu-joueur?portion=actions', 'POST'];
        yield 'portion contenu' => ['contenu-joueur?portion=contenu', 'POST'];
        yield 'portion tout' => ['contenu-joueur', 'POST'];
        yield 'photos' => ['photos', 'POST'];
        yield 'étiquettes' => ['etiquettes', 'POST'];
    }

    #[DataProvider('ecrituresFermees')]
    public function testLeJoueurNEcritPasLeContenuPhysique(string $suite, string $methode): void
    {
        $client = static::createClient();
        [$gn, $user, $dossier] = $this->dossierDeJoueur();
        $client->loginUser($user);

        $client->request($methode, $this->url($gn, $dossier, $suite));

        static::assertResponseRedirects('/access_denied');
        static::assertArrayNotHasKey('contenu_joueur', $this->recharger($dossier)->getDonnees());
    }

    public function testLeTexteLibreDuJoueurAttendUneConfirmation(): void
    {
        $client = static::createClient();
        [$gn, $user, $dossier] = $this->dossierDeJoueur();
        $client->loginUser($user);

        $crawler = $client->request('GET', $this->url($gn, $dossier, 'etape/declaration'));
        $form = $crawler->selectButton('Enregistrer mon retour')->form();
        $form->setValues(['declaration_joueur[sortiDuJeu]' => '1']);
        $form['declaration_joueur[renommee]'] = '+2 pour un discours';
        $client->submit($form);

        static::assertResponseRedirects($this->url($gn, $dossier, 'etape/declaration'));
        $declaration = static::getContainer()->get(DepouillementDeclarationService::class)->lire($this->recharger($dossier));
        static::assertSame('+2 pour un discours', $declaration->renommee);
        static::assertTrue($declaration->sortiDuJeu);
        static::assertSame(['source' => 'joueur', 'etat' => 'en_attente'], $this->decision($dossier, 'renommee'));
        static::assertSame('en_attente', $this->decision($dossier, 'sorti_du_jeu')['etat']);
        static::assertArrayNotHasKey('precisions', DecisionsDeclaration::depuis($this->recharger($dossier)->getDonnees()));
    }

    public function testLaMortDeclareeParLeJoueurAttendUneConfirmationSansCouperLaPreselection(): void
    {
        $client = static::createClient();
        [$gn, $user, $dossier] = $this->dossierDeJoueur();
        $client->loginUser($user);

        $crawler = $client->request('GET', $this->url($gn, $dossier, 'etape/declaration'));
        $form = $crawler->selectButton('Enregistrer ce que j\'ai appris')->form();
        $form->setValues(['contenu_joueur[personnageMort]' => '1']);
        $form['contenu_joueur[circonstancesMort]'] = 'Tombé à la bataille';
        $client->submit($form);

        static::assertResponseRedirects($this->url($gn, $dossier, 'etape/declaration'));
        $contenu = static::getContainer()->get(DepouillementContenuService::class)->lireContenuJoueur($this->recharger($dossier));
        static::assertTrue($contenu->personnageMort);
        static::assertSame('en_attente', $this->decision($dossier, 'personnage_mort')['etat']);
        // Le dépouilleur qui reprendra le dossier verra encore les actions pré-sélectionnées d'après les ressources trouvées.
        static::assertFalse($contenu->actionsDecidees);
    }

    public function testLeStaffCompleteSansChangerLaProvenanceDuJoueur(): void
    {
        $client = static::createClient();
        [$gn, $user, $dossier] = $this->dossierDeJoueur();
        $client->loginUser($user);
        $crawler = $client->request('GET', $this->url($gn, $dossier, 'etape/declaration'));
        $form = $crawler->selectButton('Enregistrer mon retour')->form();
        $form['declaration_joueur[renommee]'] = '+2 pour un discours';
        $client->submit($form);

        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_WARGAME']]));
        $crawler = $client->request('GET', $this->url($gn, $dossier, 'etape/declaration'));
        static::assertSelectorTextContains('body', 'Déclaré par le joueur');
        $form = $crawler->selectButton('Enregistrer mon retour')->form();
        $form['declaration_joueur[precisions]'] = 'Maladie constatée';
        $client->submit($form);

        static::assertSame('joueur', $this->decision($dossier, 'renommee')['source']);
        static::assertSame(['source' => 'depouilleur', 'etat' => 'confirme'], $this->decision($dossier, 'precisions'));
    }

    public function testApresLaRepriseLeJoueurNePeutPlusDeclarer(): void
    {
        $client = static::createClient();
        [$gn, $user, $dossier] = $this->dossierDeJoueur();
        $staff = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_SCENARISTE']]);
        static::getContainer()->get(DepouillementService::class)->reprendre($dossier, $staff);
        $client->loginUser($user);

        $client->request('GET', $this->url($gn, $dossier, 'etape/declaration'));
        static::assertResponseIsSuccessful();
        static::assertSelectorNotExists('form[name="declaration_joueur"]');

        $client->request('POST', $this->url($gn, $dossier, 'declaration'));
        static::assertResponseRedirects('/access_denied');
    }

    public function testLeStaffGardeLeParcoursComplet(): void
    {
        $client = static::createClient();
        [$gn, , $dossier] = $this->dossierDeJoueur();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_WARGAME']]));

        foreach (['identification', 'photos', 'etiquettes', 'contenu', 'actions', 'declaration'] as $etape) {
            $client->request('GET', $this->url($gn, $dossier, 'etape/' . $etape));
            static::assertResponseIsSuccessful($etape);
        }
    }
}
