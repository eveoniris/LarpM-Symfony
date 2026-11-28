<?php

declare(strict_types=1);

namespace App\Tests\Functional\InterJeu;

use App\Entity\InterJeu;
use App\Entity\InterJeuOrga;
use App\Entity\Personnage;
use App\Entity\User;
use App\Enum\InterJeuParticipantStatut;
use App\Tests\Factory\CompetenceFactory;
use App\Tests\Factory\CompetenceFamilyFactory;
use App\Tests\Factory\InterJeuFactory;
use App\Tests\Factory\LevelFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Statut des participants d'inter-jeu, statistiques (petit matériel) et accès des orgas externes.
 */
#[Group('functional')]
class InterJeuStatutOrgaTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testStatistiquesComptentUnPersonnageUneFoisAuNiveauMaxEtIgnorentLesDesistes(): void
    {
        $gestionnaire = UserFactory::createOne(['roles' => ['ROLE_INTER_JEU']]);
        $interJeu = InterJeuFactory::createOne();

        $vol = CompetenceFamilyFactory::createOne(['label' => 'Vol test']);
        $apprenti = LevelFactory::createOne(['index' => 1, 'label' => 'Apprenti']);
        $initie = LevelFactory::createOne(['index' => 2, 'label' => 'Initié']);
        $volApprenti = CompetenceFactory::createOne(['competenceFamily' => $vol, 'level' => $apprenti]);
        $volInitie = CompetenceFactory::createOne(['competenceFamily' => $vol, 'level' => $initie]);

        $voleurInitie = PersonnageFactory::createOne(['vivant' => true, 'nom' => 'Voleur initie']);
        $voleurApprenti = PersonnageFactory::createOne(['vivant' => true, 'nom' => 'Voleur apprenti']);
        $voleurDesiste = PersonnageFactory::createOne(['vivant' => true, 'nom' => 'Voleur desiste']);

        $em = $this->em();
        $inter = $em->find(InterJeu::class, $interJeu->getId());
        foreach ([[$voleurInitie, [$volApprenti, $volInitie]], [$voleurApprenti, [$volApprenti]], [$voleurDesiste, [$volApprenti]]] as [$proxy, $competences]) {
            $personnage = $em->find(Personnage::class, $proxy->getId());
            foreach ($competences as $competence) {
                // personnages_competences appartient à Competence (côté propriétaire)
                $em->find(\App\Entity\Competence::class, $competence->getId())->addPersonnage($personnage);
            }
            $inter->addPersonnage($personnage);
        }
        $inter->addPersonnage($em->find(Personnage::class, $voleurDesiste->getId()), InterJeuParticipantStatut::DESISTE);
        $em->flush();

        $this->client->loginUser($gestionnaire);
        $crawler = $this->client->request('GET', '/inter-jeu/' . $interJeu->getId() . '/statistiques');

        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('h5', '2 inscrit(s)');
        // Ligne « Vol test » : 2 personnages (pas 3 lignes de compétence, pas le désisté), 1 apprenti et 1 initié
        $ligne = $crawler->filter('table tr')->reduce(static fn ($tr): bool => str_contains($tr->text(), 'Vol test'));
        static::assertCount(1, $ligne);
        $cellules = $ligne->filter('td')->each(static fn ($td): string => trim($td->text()));
        static::assertSame(['Vol test', '2', '1', '1', '', '', ''], $cellules);

        $this->client->request('GET', '/inter-jeu/' . $interJeu->getId() . '/statistiques/export/personnages.csv');
        static::assertResponseIsSuccessful();
        $csv = (string) $this->client->getInternalResponse()->getContent();
        $lignesCsv = array_values(array_filter(explode("\n", $csv), static fn (string $l): bool => str_contains($l, 'Voleur initie')));
        static::assertCount(1, $lignesCsv);
        static::assertStringContainsString('Initié', $lignesCsv[0]);
        static::assertStringNotContainsString('Voleur desiste', $csv);
    }

    public function testImportCsvAvecStatutEtIdentifiantsIntrouvables(): void
    {
        $gestionnaire = UserFactory::createOne(['roles' => ['ROLE_INTER_JEU']]);
        $interJeu = InterJeuFactory::createOne();
        $p1 = PersonnageFactory::createOne();
        $p2 = PersonnageFactory::createOne();

        $fichier = tempnam(sys_get_temp_dir(), prefix: 'ij') . '.csv';
        file_put_contents($fichier, "id;statut\n{$p1->getId()};désisté\n{$p2->getId()}\n999999;inscrit\n");

        $this->client->loginUser($gestionnaire);
        $crawler = $this->client->request('GET', '/inter-jeu/' . $interJeu->getId() . '/personnages/import');
        $form = $crawler->selectButton('Importer')->form();
        $form['inter_jeu_personnages_import[fichier]']->upload($fichier);
        $this->client->submit($form);

        static::assertResponseRedirects('/inter-jeu/' . $interJeu->getId() . '/personnages');
        $avertissements = $this->client->getRequest()->getSession()->getFlashBag()->peek('warning');
        static::assertStringContainsString('999999', implode(' ', $avertissements));

        $inter = $this->em()->find(InterJeu::class, $interJeu->getId());
        static::assertSame(InterJeuParticipantStatut::DESISTE, $inter->getParticipation($this->em()->find(Personnage::class, $p1->getId()))?->getStatut());
        static::assertSame(InterJeuParticipantStatut::INSCRIT, $inter->getParticipation($this->em()->find(Personnage::class, $p2->getId()))?->getStatut());
        static::assertCount(1, $inter->getPersonnages());
    }

    public function testOrgaSansAccesEstRefuse(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $interJeu = InterJeuFactory::createOne();

        $this->client->loginUser($user);
        $this->client->request('GET', '/inter-jeu/' . $interJeu->getId() . '/statistiques');
        static::assertResponseRedirects('/access_denied');

        $this->client->request('GET', '/inter-jeu/' . $interJeu->getId() . '/consultation');
        static::assertResponseRedirects('/access_denied');
    }

    public function testDonnerAccesPuisPropositionValideeParLeGestionnaire(): void
    {
        $gestionnaire = UserFactory::createOne(['roles' => ['ROLE_INTER_JEU']]);
        $orga = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $interJeu = InterJeuFactory::createOne();
        $personnage = PersonnageFactory::createOne(['nom' => 'Propose par orga']);

        // Le gestionnaire donne l'accès
        $this->client->loginUser($gestionnaire);
        $crawler = $this->client->request('GET', '/inter-jeu/' . $interJeu->getId() . '/orgas');
        $form = $crawler->selectButton('Donner l\'accès')->form(['user' => (string) $orga->getId()]);
        $this->client->submit($form);
        static::assertResponseRedirects();

        $orgaUser = $this->em()->find(User::class, $orga->getId());
        static::assertContains('ROLE_ORGA_INTER', $orgaUser->getRoles());

        // L'orga consulte, ne gère pas, et propose un ajout
        $this->client->loginUser($orgaUser);
        $this->client->request('GET', '/inter-jeu/' . $interJeu->getId() . '/personnages');
        static::assertResponseRedirects('/access_denied');

        $crawler = $this->client->request('GET', '/inter-jeu/' . $interJeu->getId() . '/consultation');
        static::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Proposer l\'ajout')->form(['personnage' => (string) $personnage->getId()]);
        $this->client->submit($form);
        static::assertResponseRedirects('/inter-jeu/' . $interJeu->getId() . '/consultation');

        $this->em()->clear();
        $inter = $this->em()->find(InterJeu::class, $interJeu->getId());
        $participation = $inter->getParticipation($this->em()->find(Personnage::class, $personnage->getId()));
        static::assertNotNull($participation);
        static::assertSame(InterJeuParticipantStatut::EN_ATTENTE, $participation->getStatut());
        static::assertSame(InterJeuParticipantStatut::INSCRIT, $participation->getStatutPropose());

        // Le gestionnaire valide
        $this->client->loginUser($this->em()->find(User::class, $gestionnaire->getId()));
        $crawler = $this->client->request('GET', '/inter-jeu/' . $interJeu->getId() . '/personnages');
        $form = $crawler->selectButton('Valider')->form();
        $this->client->submit($form, ['decision' => 'valider']);
        static::assertResponseRedirects();

        $this->em()->clear();
        $inter = $this->em()->find(InterJeu::class, $interJeu->getId());
        $participation = $inter->getParticipation($this->em()->find(Personnage::class, $personnage->getId()));
        static::assertSame(InterJeuParticipantStatut::INSCRIT, $participation?->getStatut());
        static::assertFalse($participation->hasProposition());
    }

    public function testRetirerLeDernierAccesRetireLeRole(): void
    {
        $gestionnaire = UserFactory::createOne(['roles' => ['ROLE_INTER_JEU']]);
        $orga = UserFactory::createOne(['roles' => ['ROLE_ORGA_INTER']]);
        $interJeu = InterJeuFactory::createOne();

        $em = $this->em();
        $inter = $em->find(InterJeu::class, $interJeu->getId());
        $acces = $inter->addOrga($em->find(User::class, $orga->getId()));
        $em->flush();

        $this->client->loginUser($gestionnaire);
        $crawler = $this->client->request('GET', '/inter-jeu/' . $interJeu->getId() . '/orgas');
        $this->client->submit($crawler->selectButton('Retirer l\'accès')->form());
        static::assertResponseRedirects();

        $this->em()->clear();
        static::assertNull($this->em()->find(InterJeuOrga::class, $acces->getId()));
        static::assertNotContains('ROLE_ORGA_INTER', $this->em()->find(User::class, $orga->getId())->getRoles());
    }

    public function testAjoutProposeMasqueLeNomEtRefusSupprimeLaLigne(): void
    {
        $gestionnaire = UserFactory::createOne(['roles' => ['ROLE_INTER_JEU']]);
        $orga = UserFactory::createOne(['roles' => ['ROLE_ORGA_INTER']]);
        $interJeu = InterJeuFactory::createOne();
        $personnage = PersonnageFactory::createOne(['nom' => 'Nom cache a l orga']);

        $em = $this->em();
        $em->find(InterJeu::class, $interJeu->getId())->addOrga($em->find(User::class, $orga->getId()));
        $em->flush();

        $this->client->loginUser($em->find(User::class, $orga->getId()));
        $crawler = $this->client->request('GET', '/inter-jeu/' . $interJeu->getId() . '/consultation');
        $this->client->submit($crawler->selectButton('Proposer l\'ajout')->form(['personnage' => (string) $personnage->getId()]));
        $this->client->followRedirect();
        static::assertResponseIsSuccessful();
        static::assertSelectorTextNotContains('table', 'Nom cache a l orga');
        static::assertSelectorTextContains('table', 'Ajout proposé');

        $this->client->loginUser($em->find(User::class, $gestionnaire->getId()));
        $crawler = $this->client->request('GET', '/inter-jeu/' . $interJeu->getId() . '/personnages');
        $this->client->submit($crawler->selectButton('Refuser')->form(), ['decision' => 'refuser']);
        static::assertResponseRedirects();

        $this->em()->clear();
        static::assertCount(0, $this->em()->find(InterJeu::class, $interJeu->getId())->getParticipations());
    }

    public function testOrgaDUnInterJeuNeVoitPasUnAutre(): void
    {
        $orga = UserFactory::createOne(['roles' => ['ROLE_ORGA_INTER']]);
        $interA = InterJeuFactory::createOne();
        $interB = InterJeuFactory::createOne();

        $em = $this->em();
        $em->find(InterJeu::class, $interA->getId())->addOrga($em->find(User::class, $orga->getId()));
        $em->flush();

        $this->client->loginUser($em->find(User::class, $orga->getId()));
        $this->client->request('GET', '/inter-jeu/' . $interA->getId() . '/statistiques/export/familles-cles.csv');
        static::assertResponseIsSuccessful();
        // Exports nominatifs réservés à la gestion des inter-jeux
        foreach (['/statistiques/export/personnages.csv', '/statistiques/export/personnages-par-famille.csv', '/statistiques/export.zip?nominatif=1'] as $suffixe) {
            $this->client->request('GET', '/inter-jeu/' . $interA->getId() . $suffixe);
            static::assertResponseRedirects('/access_denied', message: $suffixe);
        }

        foreach (['/statistiques', '/statistiques/export/familles-cles.csv', '/statistiques/export.zip', '/consultation'] as $suffixe) {
            $this->client->request('GET', '/inter-jeu/' . $interB->getId() . $suffixe);
            static::assertResponseRedirects('/access_denied', message: $suffixe);
        }
        $this->client->request('POST', '/inter-jeu/' . $interB->getId() . '/proposition/ajout', ['personnage' => '1']);
        static::assertResponseRedirects('/access_denied');
        $this->client->request('GET', '/inter-jeu/' . $interA->getId() . '/orgas');
        static::assertResponseRedirects('/access_denied');
    }

    public function testImportCsvExcelAvecBomEtExclusionDesMorts(): void
    {
        $gestionnaire = UserFactory::createOne(['roles' => ['ROLE_INTER_JEU']]);
        $interJeu = InterJeuFactory::createOne();
        $vivant = PersonnageFactory::createOne(['vivant' => true, 'nom' => '=HYPERLINK("x")']);
        $mort = PersonnageFactory::createOne(['vivant' => false, 'nom' => 'Personnage mort']);

        $fichier = tempnam(sys_get_temp_dir(), prefix: 'ij') . '.csv';
        file_put_contents($fichier, "\xEF\xBB\xBF{$vivant->getId()}\r\n{$mort->getId()}\r\n");

        $this->client->loginUser($gestionnaire);
        $crawler = $this->client->request('GET', '/inter-jeu/' . $interJeu->getId() . '/personnages/import');
        $form = $crawler->selectButton('Importer')->form();
        $form['inter_jeu_personnages_import[fichier]']->upload($fichier);
        $this->client->submit($form);

        $inter = $this->em()->find(InterJeu::class, $interJeu->getId());
        static::assertCount(2, $inter->getParticipations(), 'la 1re ligne avec BOM est importée');

        $this->client->request('GET', '/inter-jeu/' . $interJeu->getId() . '/statistiques/export/personnages.csv');
        $csv = (string) $this->client->getInternalResponse()->getContent();
        static::assertStringNotContainsString('Personnage mort', $csv);
        static::assertStringContainsString("'=HYPERLINK", $csv, 'formule neutralisée');
    }

    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }
}
