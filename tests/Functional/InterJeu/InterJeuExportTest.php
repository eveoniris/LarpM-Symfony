<?php

declare(strict_types=1);

namespace App\Tests\Functional\InterJeu;

use App\Entity\Competence;
use App\Entity\InterJeu;
use App\Entity\Personnage;
use App\Entity\User;
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
use ZipArchive;

/**
 * Export des statistiques d'un inter-jeu : zip anonyme pour les orgas externes, nominatif pour la gestion.
 */
#[Group('functional')]
class InterJeuExportTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testExportZipAnonymePourLOrgaEtNominatifPourLaGestion(): void
    {
        $gestionnaire = UserFactory::createOne(['roles' => ['ROLE_INTER_JEU']]);
        $orga = UserFactory::createOne(['roles' => ['ROLE_ORGA_INTER']]);
        $interJeu = InterJeuFactory::createOne(['nom' => 'Inter export']);
        $vol = CompetenceFamilyFactory::createOne(['label' => 'Vol export']);
        $apprenti = LevelFactory::createOne(['index' => 1, 'label' => 'Apprenti']);
        $competence = CompetenceFactory::createOne(['competenceFamily' => $vol, 'level' => $apprenti]);
        $voleur = PersonnageFactory::createOne(['vivant' => true, 'nom' => 'Voleur exporte']);

        $em = $this->em();
        $inter = $em->find(InterJeu::class, $interJeu->getId());
        $personnage = $em->find(Personnage::class, $voleur->getId());
        $em->find(Competence::class, $competence->getId())->addPersonnage($personnage);
        $inter->addPersonnage($personnage);
        $inter->addOrga($em->find(User::class, $orga->getId()));
        $em->flush();

        $this->client->loginUser($em->find(User::class, $orga->getId()));
        $anonyme = $this->contenuZip('/inter-jeu/' . $interJeu->getId() . '/statistiques/export.zip');
        static::assertSame(['familles-cles.csv', 'lisez-moi.txt', 'toutes-familles.csv'], array_keys($anonyme));
        static::assertStringStartsWith("\xEF\xBB\xBF", $anonyme['toutes-familles.csv']);
        static::assertStringContainsString('"Vol export";1;1;0;0;0;0', $anonyme['toutes-familles.csv']);
        static::assertStringNotContainsString('Voleur exporte', implode('', $anonyme));
        static::assertStringNotContainsString((string) $voleur->getId() . ';', $anonyme['toutes-familles.csv']);

        $this->client->loginUser($em->find(User::class, $gestionnaire->getId()));
        $nominatif = $this->contenuZip('/inter-jeu/' . $interJeu->getId() . '/statistiques/export.zip?nominatif=1');
        static::assertSame(['familles-cles.csv', 'lisez-moi.txt', 'personnages-par-famille.csv', 'personnages.csv', 'toutes-familles.csv'], array_keys($nominatif));
        static::assertStringContainsString(\sprintf('"Vol export";%d;"Voleur exporte"', $voleur->getId()), $nominatif['personnages-par-famille.csv']);
    }

    /**
     * @return array<string, string> nom de fichier => contenu, triés par nom
     */
    private function contenuZip(string $url): array
    {
        $this->client->request('GET', $url);
        static::assertResponseIsSuccessful();
        static::assertResponseHeaderSame('Content-Type', 'application/zip');

        $chemin = (string) tempnam(sys_get_temp_dir(), prefix: 'ijzip');
        file_put_contents($chemin, (string) $this->client->getInternalResponse()->getContent());
        $zip = new ZipArchive();
        static::assertTrue($zip->open($chemin));
        $fichiers = [];
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $nom = (string) $zip->getNameIndex($i);
            $fichiers[$nom] = (string) $zip->getFromName($nom);
        }
        $zip->close();
        unlink($chemin);
        ksort($fichiers);

        return $fichiers;
    }

    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }
}
