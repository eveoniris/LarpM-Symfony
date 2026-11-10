<?php

declare(strict_types=1);

namespace App\Tests\Functional\Depouillement;

use App\Entity\FicheRetourGroupe;
use App\Entity\Gn;
use App\Entity\GroupeGn;
use App\Entity\User;
use App\Enum\DepouillementType;
use App\Service\DepouillementRetourGroupeService;
use App\Service\DepouillementService;
use App\Service\FicheRetourGroupeImportService;
use App\Service\RetourExportService;
use App\Service\RetourGroupe;
use App\Tests\Factory\EtatCivilFactory;
use App\Tests\Factory\FicheRetourGroupeFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Exports CSV du wargame : les fiches de groupe font l'aller-retour avec l'import, les retours sont réservés au wargame et neutralisent
 * les formules de tableur.
 */
#[Group('functional')]
class RetourExportTest extends WebTestCase
{
    private KernelBrowser $client;

    private Gn $gn;

    private GroupeGn $groupeGn;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->gn = GnFactory::createOne();
        $this->groupeGn = GroupeGnFactory::createOne(['groupe' => GroupeFactory::createOne(), 'gn' => $this->gn]);
    }

    private function wargame(): User
    {
        return UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_WARGAME']]);
    }

    public function testLesFichesDeGroupeFontLAllerRetourAvecLImport(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $fiche = FicheRetourGroupeFactory::createOne(['groupeGn' => $this->groupeGn, 'submitted_at' => new \DateTime('2026-06-01 14:00:00'), 'commentaire' => 'Tout va bien']);
        $avant = $fiche->toArray();
        $export = static::getContainer()->get(RetourExportService::class);

        $lignes = $export->fichesGroupe($this->gn);
        static::assertCount(2, $lignes);
        static::assertSame(RetourExportService::ENTETES_FICHES, $lignes[0]);

        // On réimporte le fichier exporté sur une fiche remise à zéro : on retrouve exactement les mêmes valeurs.
        $chemin = (string) tempnam(sys_get_temp_dir(), 'fiche');
        file_put_contents($chemin, $export->csv($lignes));
        $fiche->setPiecesArgent(0)->setSel(0)->setVin(0)->setCommentaire(null);
        $em->flush();
        $resultat = static::getContainer()
            ->get(FicheRetourGroupeImportService::class)
            ->import(new UploadedFile($chemin, 'fiches.csv', 'text/csv', null, true), $this->gn, UserFactory::createOne());
        $em->clear();

        static::assertSame(1, $resultat['imported']);
        $apres = $em->getRepository(FicheRetourGroupe::class)->findOneBy(['groupeGn' => $this->groupeGn->getId()]);
        static::assertInstanceOf(FicheRetourGroupe::class, $apres);
        static::assertSame($avant['pieces_argent'] ?? null, $apres->toArray()['pieces_argent'] ?? null);
        static::assertSame($fiche->getSel(), $apres->getSel());
        static::assertSame($fiche->getVin(), $apres->getVin());
        static::assertSame('Tout va bien', $apres->getCommentaire());
    }

    public function testLeTexteNeutraliseLesFormules(): void
    {
        static::assertSame("'=1+1", RetourExportService::texte('=1+1'));
        static::assertSame("'@SUM(A1)", RetourExportService::texte('@SUM(A1)'));
        static::assertSame('Normal', RetourExportService::texte('  Normal '));
        static::assertSame('', RetourExportService::texte(null));
    }

    public function testLExportDesRetoursDeGroupeContientTitresEtOrdres(): void
    {
        $staff = $this->wargame();
        $dossier = static::getContainer()
            ->get(DepouillementService::class)
            ->ouvrirReel($this->gn, DepouillementType::GROUPE, (string) $this->groupeGn->getGroupe()->getNumero(), $staff);
        $retour = new RetourGroupe();
        $retour->ordres1 = '=cmd|calc';
        static::getContainer()->get(DepouillementRetourGroupeService::class)->enregistrer($dossier, $retour, $staff);

        $lignes = static::getContainer()->get(RetourExportService::class)->retoursGroupe($this->gn);

        static::assertCount(2, $lignes);
        static::assertContains("'=cmd|calc", $lignes[1]);
        static::assertContains('Ordres phase 1', $lignes[0]);
    }

    public function testSeulLeWargameTelecharge(): void
    {
        $client = $this->client;
        $joueur = UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST']]);

        $client->loginUser($joueur);
        foreach (['fiches-groupe', 'retours-groupe', 'retours-joueurs'] as $export) {
            $client->request('GET', '/gn/' . $this->gn->getId() . '/export/' . $export . '.csv');
            static::assertResponseRedirects('/access_denied');
        }

        $client->loginUser($this->wargame());
        $client->request('GET', '/gn/' . $this->gn->getId() . '/export/retours-joueurs.csv');
        static::assertResponseIsSuccessful();
        static::assertStringStartsWith("\xEF\xBB\xBF", (string) $client->getResponse()->getContent());
        static::assertStringContainsString('text/csv', (string) $client->getResponse()->headers->get('Content-Type'));
    }

    public function testLEtatCivilDuJoueurEstReserveAuxRolesDeGestion(): void
    {
        $joueur = UserFactory::createOne(['etatCivil' => EtatCivilFactory::createOne(['nom' => 'Dupontel', 'prenom' => 'Alice'])]);
        $personnage = PersonnageFactory::createOne(['user' => $joueur]);
        ParticipantFactory::createOne(['user' => $joueur, 'gn' => $this->gn, 'groupeGn' => $this->groupeGn, 'personnage' => $personnage]);
        $staff = $this->wargame();
        static::getContainer()
            ->get(DepouillementService::class)
            ->ouvrirReel($this->gn, DepouillementType::JOUEUR, (string) $personnage->getId(), $staff);
        $url = '/gn/' . (int) $this->gn->getId() . '/export/retours-joueurs.csv';

        // Par défaut : aucune donnée sur le joueur, même pour un wargame et même si on la demande.
        $lignes = static::getContainer()->get(RetourExportService::class)->retoursJoueurs($this->gn);
        static::assertNotContains('Joueur (état civil)', $lignes[0]);
        static::assertCount(2, $lignes);

        $this->client->loginUser($staff);
        $this->client->request('GET', $url . '?etat_civil=1');
        static::assertResponseIsSuccessful();
        static::assertStringNotContainsString('Dupontel', (string) $this->client->getResponse()->getContent());

        // Gestion : la colonne apparaît seulement sur demande explicite.
        $this->client->loginUser(UserFactory::createOne(['roles' => ['ROLE_USER', 'ROLE_BETA_TEST', 'ROLE_WARGAME', 'ROLE_GESTION']]));
        $this->client->request('GET', $url);
        static::assertStringNotContainsString('Dupontel', (string) $this->client->getResponse()->getContent());
        $this->client->request('GET', $url . '?etat_civil=1');
        static::assertStringContainsString('Joueur (état civil)', (string) $this->client->getResponse()->getContent());
        static::assertStringContainsString('Alice Dupontel', (string) $this->client->getResponse()->getContent());
    }

    public function testLaColonneGroupeNumeroEstPrioritaireALImport(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $fiche = FicheRetourGroupeFactory::createOne(['groupeGn' => $this->groupeGn, 'submitted_at' => new \DateTime('2026-06-01 14:00:00')]);
        $numero = (int) $this->groupeGn->getGroupe()->getNumero();
        static::assertContains('groupe_numero', RetourExportService::ENTETES_FICHES);

        // Le libellé du groupe est inutilisable (aucun numéro) : seule la colonne dédiée permet de retrouver le groupe.
        $chemin = (string) tempnam(sys_get_temp_dir(), 'fiche');
        file_put_contents($chemin, "Groupe concerné,Sel,groupe_numero\nLibellé sans numéro,42,{$numero}\n");
        $fiche->setSel(0);
        $em->flush();
        $resultat = static::getContainer()
            ->get(FicheRetourGroupeImportService::class)
            ->import(new UploadedFile($chemin, 'fiches.csv', 'text/csv', null, true), $this->gn, UserFactory::createOne());

        static::assertSame(1, $resultat['imported']);
        static::assertSame(42, $fiche->getSel());
    }
}
