<?php

declare(strict_types=1);

namespace App\Tests\Functional\Depouillement;

use App\Entity\Depouillement;
use App\Entity\User;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Contenu d'une enveloppe de groupe dans l'étape Contenu : formulaire, matériel, 24 cases et reliquat du groupe.
 */
#[Group('functional')]
class DepouillementContenuGroupeTest extends WebTestCase
{
    private function beta(): User
    {
        return UserFactory::createOne(['username' => 'moi', 'roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]);
    }

    private function enveloppeGroupe(User $depouilleur): Depouillement
    {
        $groupeGn = GroupeGnFactory::createOne();
        $depouillement = (new Depouillement())
            ->setGn($groupeGn->getGn())
            ->setType(DepouillementType::GROUPE)
            ->setNumero((string) $groupeGn->getGroupe()->getNumero())
            ->setGroupeGn($groupeGn)
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

    public function testLEtapeContenuAfficheLeFormulaireDeGroupe(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->enveloppeGroupe($moi);
        $client->loginUser($moi);

        $client->request('GET', $this->contenu($depouillement));

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('form[name="contenu_groupe"]');
        static::assertSelectorNotExists('form[name="contenu_joueur"]');
        static::assertSelectorCount(24, 'form[name="contenu_groupe"] fieldset:last-of-type input[type=number]');
        static::assertSelectorTextContains('body', 'Reliquat du groupe');
        static::assertSelectorTextContains('body', 'Aucun reliquat pour l\'instant');
    }

    public function testEnregistrementEtReliquatAvecLeSurplusDesJoueursDuGroupe(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->enveloppeGroupe($moi);
        $groupeGn = $depouillement->getGroupeGn();
        $participant = ParticipantFactory::createOne([
            'gn' => $depouillement->getGn(),
            'user' => UserFactory::createOne(),
            'personnage' => PersonnageFactory::createOne(),
            'groupeGn' => $groupeGn,
        ]);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist((new Depouillement())
            ->setGn($depouillement->getGn())
            ->setType(DepouillementType::JOUEUR)
            ->setNumero('1')
            ->setParticipant($participant)
            ->setSimulation(true)
            ->setDonnees(['contenu_joueur' => ['surplus' => ['bois' => 2]]]));
        $em->flush();
        $client->loginUser($moi);

        $crawler = $client->request('GET', $this->contenu($depouillement));
        $form = $crawler->selectButton('Enregistrer le contenu du groupe')->form();
        $form['contenu_groupe[ficheRetour]']->tick();
        $form['contenu_groupe[materiel][pieges]'] = '3';
        $form['contenu_groupe[autreMateriel]'] = 'Une corne de brume';
        $form['contenu_groupe[compteurs][bois]'] = '5';
        $client->submit($form);

        static::assertResponseRedirects($this->contenu($depouillement));
        $client->followRedirect();
        static::assertSelectorTextContains('body', 'Contenu de l\'enveloppe de groupe enregistré');
        static::assertSelectorTextContains('body', '1 enveloppe(s) de joueur');
        static::assertSelectorTextContains('table.w-auto', 'Bois');
        // Ligne du reliquat : 5 (groupe) + 2 (surplus du joueur) = 7.
        static::assertSame('Bois 5 2 7', preg_replace('/\s+/', replacement: ' ', subject: trim($client->getCrawler()->filter('table.w-auto tbody tr')->first()->text())));

        $em->clear();
        $relu = $em->find(Depouillement::class, $depouillement->getId());
        static::assertSame(3, $relu->getDonnees()['contenu_groupe']['materiel']['pieges']);
        static::assertTrue($relu->getDonnees()['contenu_groupe']['fiche_retour']);
    }

    public function testQuandLaSaisieEstTermineeLeContenuDuGroupeEstEnLectureSeule(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $depouillement = $this->enveloppeGroupe($moi);
        $client->loginUser($moi);

        $crawler = $client->request('GET', $this->contenu($depouillement));
        $form = $crawler->selectButton('Enregistrer le contenu du groupe')->form();
        $form['contenu_groupe[materiel][cadenas]'] = '2';
        $client->submit($form);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $em->find(Depouillement::class, $depouillement->getId())->setStatut(DepouillementStatut::A_VALIDER);
        $em->flush();

        $client->request('GET', $this->contenu($depouillement));
        static::assertSelectorNotExists('form[name="contenu_groupe"]');
        static::assertSelectorTextContains('body', 'Cadenas avec leurs clés : 2');
    }

    public function testUneEnveloppeDeJoueurNePeutPasEnregistrerLeContenuDeGroupe(): void
    {
        $client = static::createClient();
        $moi = $this->beta();
        $enveloppeGroupe = $this->enveloppeGroupe($moi);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $enveloppeJoueur = (new Depouillement())
            ->setGn($enveloppeGroupe->getGn())
            ->setType(DepouillementType::JOUEUR)
            ->setNumero('1')
            ->setSimulation(true)
            ->setDepouilleur($moi);
        $em->persist($enveloppeJoueur);
        $em->flush();
        $client->loginUser($moi);

        $crawler = $client->request('GET', $this->contenu($enveloppeGroupe));
        $form = $crawler->selectButton('Enregistrer le contenu du groupe')->form();
        $form->getNode()->setAttribute('action', '/gn/' . $enveloppeGroupe->getGn()->getId() . '/depouillement/' . $enveloppeJoueur->getId() . '/contenu-groupe');
        $client->submit($form);
        $client->followRedirect();

        static::assertSelectorTextContains('body', 'Ce contenu ne concerne que les enveloppes de groupe');
    }

    public function testSansLeRoleBetaOnNEnregistreRien(): void
    {
        $client = static::createClient();
        $depouillement = $this->enveloppeGroupe($this->beta());
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_SCENARISTE']]));

        $client->request('POST', '/gn/' . $depouillement->getGn()->getId() . '/depouillement/' . $depouillement->getId() . '/contenu-groupe');

        static::assertResponseRedirects('/access_denied');
    }
}
