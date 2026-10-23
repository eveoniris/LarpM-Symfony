<?php

declare(strict_types=1);

namespace App\Tests\Functional\Depouillement;

use App\Entity\Depouillement;
use App\Entity\User;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Contenu d'une enveloppe de joueur dans l'étape Contenu : formulaire, six actions, surplus, introuvables.
 */
#[Group('functional')]
// @mago-expect lint:too-many-methods
class DepouillementContenuJoueurTest extends WebTestCase
{
    private function beta(): User
    {
        return UserFactory::createOne(['username' => 'moi', 'roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]);
    }

    private function depouillement(User $depouilleur, DepouillementType $type = DepouillementType::JOUEUR): Depouillement
    {
        $depouillement = (new Depouillement())
            ->setGn(GnFactory::createOne())
            ->setType($type)
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

    private function actions(Depouillement $depouillement): string
    {
        return str_replace('/etape/contenu', replace: '/etape/actions', subject: $this->contenu($depouillement));
    }

    public function testLEtapeContenuAfficheLeFormulaireDeLEnveloppeDeJoueur(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);

        $client->request('GET', $this->contenu($depouillement));

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('form[name="contenu_joueur"]');
        static::assertSelectorTextContains('body', 'Ressources et pièces trouvées dans l\'enveloppe');
        static::assertSelectorCount(24, 'details input[type=number]');
        static::assertSelectorTextNotContains('body', 'Action utilisée');

        $client->request('GET', $this->actions($depouillement));
        static::assertResponseIsSuccessful();
        static::assertSelectorExists('form[name="contenu_joueur"]');
        static::assertSelectorTextContains('body', 'Fruits et légumes : prolonger l\'âge maximal');
        static::assertSelectorTextContains('body', 'Ivoire : conserver des potions');
        static::assertSelectorNotExists('details input[type=number]');
    }

    public function testUneEnveloppeDeGroupeNAPasCeFormulaire(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi, DepouillementType::GROUPE);
        $client->loginUser($moi);

        $client->request('GET', $this->contenu($depouillement));

        static::assertResponseIsSuccessful();
        static::assertSelectorNotExists('form[name="contenu_joueur"]');
    }

    public function testEnregistrementAfficheActionRefuseeSurplusEtIntrouvable(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);

        $crawler = $client->request('GET', $this->contenu($depouillement));
        $form = $crawler->selectButton('Enregistrer le contenu')->form();
        $form['contenu_joueur[fichePersonnage]']->tick();
        $form['contenu_joueur[surplus][bois]'] = '4';
        $form['contenu_joueur[introuvables]'] = 'Sort de feu inconnu';
        $client->submit($form);
        static::assertResponseRedirects($this->contenu($depouillement));
        $client->followRedirect();
        static::assertSelectorTextContains('body', 'introuvables dans les listes');

        // L'étape des actions ne touche pas au contenu déjà enregistré.
        $crawler = $client->request('GET', $this->actions($depouillement));
        $form = $crawler->selectButton('Enregistrer les actions')->form();
        $form['contenu_joueur[fruitsUtilise]']->tick();
        $form['contenu_joueur[soieUtilise]']->tick();
        $form['contenu_joueur[soieJointes]']->tick();
        $client->submit($form);

        static::assertResponseRedirects($this->actions($depouillement));
        $client->followRedirect();
        static::assertSelectorTextContains('body', 'Contenu enregistré : 1 action(s) de fin d\'Opus acceptée(s).');
        static::assertSelectorTextContains('body', 'Refusée');
        static::assertSelectorTextContains('body', 'Acceptée');
        static::assertSelectorTextContains('body', 'les 5 ressource(s) exigée(s) ne sont pas dans l\'enveloppe');
        static::assertSelectorTextContains('body', 'sans effet, non bloquant');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $relu = $em->find(Depouillement::class, $depouillement->getId());
        static::assertSame(4, $relu->getDonnees()['contenu_joueur']['surplus']['bois']);
        static::assertTrue($relu->getDonnees()['contenu_joueur']['pieces']['fiche_personnage']);
        static::assertStringContainsString('[Introuvable] Sort de feu inconnu', (string) $relu->getRemarques());
    }

    public function testUnNombreDePiecesDOrAuDelaDeTroisEstRefuse(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);

        $crawler = $client->request('GET', $this->actions($depouillement));
        $form = $crawler->selectButton('Enregistrer les actions')->form();
        $form['contenu_joueur[gemmeUtilise]']->tick();
        $form['contenu_joueur[gemmeJointes]']->tick();
        $form['contenu_joueur[gemmePieces]'] = '9';
        $client->submit($form);
        $client->followRedirect();

        static::assertSelectorTextContains('body', 'Entre 0 et 3 pièces d\'or');
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        static::assertArrayNotHasKey('contenu_joueur', $em->find(Depouillement::class, $depouillement->getId())->getDonnees());
    }

    public function testQuandLaSaisieEstTermineeLeContenuEstEnLectureSeule(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);

        $crawler = $client->request('GET', $this->actions($depouillement));
        $form = $crawler->selectButton('Enregistrer les actions')->form();
        $form['contenu_joueur[soieUtilise]']->tick();
        $form['contenu_joueur[soieJointes]']->tick();
        $client->submit($form);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $em->find(Depouillement::class, $depouillement->getId())->setStatut(DepouillementStatut::A_VALIDER);
        $em->flush();

        $client->request('GET', $this->actions($depouillement));
        static::assertSelectorNotExists('form[name="contenu_joueur"]');
        static::assertSelectorTextContains('body', 'Soie : augmenter la renommée : Acceptée');
    }

    public function testLeRecapitulatifResumeLeContenuPourLeValideur(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->depouillement($moi);
        $client->loginUser($moi);

        $crawler = $client->request('GET', $this->contenu($depouillement));
        $form = $crawler->selectButton('Enregistrer le contenu')->form();
        $form['contenu_joueur[surplus][bois]'] = '4';
        $client->submit($form);
        $crawler = $client->request('GET', $this->actions($depouillement));
        $form = $crawler->selectButton('Enregistrer les actions')->form();
        $form['contenu_joueur[soieUtilise]']->tick();
        $form['contenu_joueur[soieJointes]']->tick();
        $form['contenu_joueur[fruitsUtilise]']->tick();
        $client->submit($form);

        $client->request('GET', str_replace('/etape/contenu', replace: '/etape/recapitulatif', subject: $this->contenu($depouillement)));

        static::assertSelectorTextContains('body', 'Résumé à relire');
        static::assertSelectorTextContains('body', 'acceptées : Soie');
        static::assertSelectorTextContains('body', 'refusées : Fruits et légumes');
        static::assertSelectorTextContains('body', 'Surplus à reverser au groupe : Bois : 4');
        static::assertSelectorTextContains('body', 'Photos : 0');
        static::assertSelectorNotExists('form[name="contenu_joueur"]');
    }

    public function testSansLeRoleBetaOnNEnregistreRien(): void
    {
        $client = static::createClient();
        $depouillement = $this->depouillement($this->beta());
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_SCENARISTE']]));

        $client->request('POST', '/gn/' . $depouillement->getGn()->getId() . '/depouillement/' . $depouillement->getId() . '/contenu-joueur');

        static::assertResponseRedirects('/access_denied');
    }
}
