<?php

declare(strict_types=1);

namespace App\Tests\Functional\Depouillement;

use App\Entity\Depouillement;
use App\Entity\DepouillementEtiquette;
use App\Entity\User;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Enum\EtiquetteDestination;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Étiquettes dans l'étape Contenu : formulaire, règles affichées, anomalies, suppression, verrouillage.
 */
// @mago-expect lint:too-many-methods
#[Group('functional')]
class DepouillementEtiquetteTest extends WebTestCase
{
    private function beta(): User
    {
        return UserFactory::createOne(['username' => 'moi', 'roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]);
    }

    private function depouillement(User $depouilleur): Depouillement
    {
        $depouillement = (new Depouillement())
            ->setGn(GnFactory::createOne())
            ->setType(DepouillementType::JOUEUR)
            ->setNumero('1')
            ->setSimulation(true)
            ->setDepouilleur($depouilleur);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($depouillement);
        $em->flush();

        return $depouillement;
    }

    private function contenu(Depouillement $depouillement): string
    {
        return '/gn/' . $depouillement->getGn()->getId() . '/depouillement/' . $depouillement->getId() . '/etape/contenu';
    }

    /**
     * @param array<string, string|int> $valeurs
     */
    private function ajouter(KernelBrowser $client, Depouillement $depouillement, array $valeurs): void
    {
        $crawler = $client->request('GET', $this->contenu($depouillement));
        $champs = [];
        foreach ($valeurs as $nom => $valeur) {
            $champs['depouillement_etiquette[' . $nom . ']'] = $valeur;
        }

        $client->submit($crawler->selectButton('Ajouter l\'étiquette')->form($champs));
    }

    /**
     * @return list<DepouillementEtiquette>
     */
    private function etiquettes(): array
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        return array_values($em->getRepository(DepouillementEtiquette::class)->findAll());
    }

    public function testLEtapeContenuAfficheLeFormulaireEtLaListeVide(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);

        $client->request('GET', $this->contenu($depouillement));

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('form[name="depouillement_etiquette"]');
        static::assertSelectorTextContains('body', 'Aucune étiquette saisie');
    }

    public function testUnLotDEtiquettesDArmesGeneriquesEstCompteAuStock(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);

        $this->ajouter($client, $depouillement, ['nature' => 'arme', 'couleur' => 'orange', 'quantite' => 5]);

        static::assertResponseRedirects($this->contenu($depouillement));
        $etiquettes = $this->etiquettes();
        static::assertCount(1, $etiquettes);
        static::assertSame(5, $etiquettes[0]->getQuantite());
        static::assertSame(EtiquetteDestination::STOCK, $etiquettes[0]->getDestination());

        $client->followRedirect();
        static::assertSelectorTextContains('body', '5 étiquette(s) remise(s) au stock');
        static::assertSelectorTextContains('body', 'Remise au stock');
    }

    public function testUneEtiquetteBleueSansObjetAfficheUneAnomalie(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);

        $this->ajouter($client, $depouillement, ['nature' => 'arme', 'couleur' => 'bleue', 'qualite' => '263', 'situation' => 'sans_objet']);
        $client->followRedirect();

        static::assertSelectorTextContains('body', 'objet est manquant');
        static::assertSelectorTextContains('body', 'Au joueur, à rapporter au PC joueur');
        static::assertSelectorTextContains('body', 'Des anomalies sont signalées');
    }

    public function testPlusieursBannieresSontComptees(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);

        $this->ajouter($client, $depouillement, ['nature' => 'banniere', 'quantite' => 3]);
        $client->followRedirect();

        static::assertSelectorTextContains('body', '3 bannière(s) laissée(s) dans l\'enveloppe');
        static::assertSelectorTextContains('body', 'Plusieurs bannières (3)');
        static::assertSelectorTextNotContains('body', 'À prévoir pour l\'opus suivant');
    }

    public function testUneEtiquetteSpecialeEstAPrevoirPourLOpusSuivant(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);

        $this->ajouter($client, $depouillement, ['nature' => 'arme', 'couleur' => 'orange', 'qualite' => '721', 'situation' => 'sans_objet']);
        $client->followRedirect();

        static::assertSelectorTextContains('body', 'À prévoir pour l\'opus suivant');
        static::assertSelectorTextContains('body', 'spéciale');
        static::assertTrue($this->etiquettes()[0]->isAPrevoirProchainOpus());
    }

    public function testUneSaisieInvalideAfficheLeMessageEtNEnregistreRien(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);

        // Étiquette d'objet sans numéro.
        $this->ajouter($client, $depouillement, ['nature' => 'objet', 'couleur' => 'orange', 'qualite' => '263']);
        $client->followRedirect();
        static::assertSelectorTextContains('body', 'Le numéro (en haut à gauche de l\'étiquette) est obligatoire');

        // Qualité à deux chiffres.
        $this->ajouter($client, $depouillement, ['nature' => 'arme', 'couleur' => 'orange', 'qualite' => '62']);
        $client->followRedirect();
        static::assertSelectorTextContains('body', 'La qualité a exactement trois chiffres');

        static::assertCount(0, $this->etiquettes());
    }

    public function testSuppressionDUneEtiquette(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);
        $this->ajouter($client, $depouillement, ['nature' => 'arme', 'couleur' => 'orange', 'quantite' => 2]);

        $crawler = $client->request('GET', $this->contenu($depouillement));
        $client->submit($crawler->filter('form[action$="/supprimer"]')->form());

        static::assertResponseRedirects($this->contenu($depouillement));
        static::assertCount(0, $this->etiquettes());
    }

    public function testRienNEstModifiableQuandLaSaisieEstTerminee(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);
        $this->ajouter($client, $depouillement, ['nature' => 'arme', 'couleur' => 'orange', 'quantite' => 2]);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $em->find(Depouillement::class, $depouillement->getId())->setStatut(DepouillementStatut::A_VALIDER);
        $em->flush();

        $client->request('GET', $this->contenu($depouillement));
        static::assertSelectorNotExists('form[name="depouillement_etiquette"]');
        static::assertSelectorNotExists('form[action$="/supprimer"]');
        static::assertCount(1, $this->etiquettes());
    }

    public function testSansLeRoleBetaOnNAccedePasAuxEtiquettes(): void
    {
        $client = static::createClient();
        $depouillement = $this->depouillement($this->beta());
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_SCENARISTE']]));

        $client->request('POST', '/gn/' . $depouillement->getGn()->getId() . '/depouillement/' . $depouillement->getId() . '/etiquettes');

        static::assertResponseRedirects('/access_denied');
    }
}
