<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\RulebookDomaine;
use App\Entity\RulebookRegle;
use App\Enum\RulebookStatut;
use App\Service\Rulebook\RulebookExporter;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Export du cache local du rulebook (dossier de test hors du dépôt). DAMA annule les données. */
#[Group('integration')]
class RulebookExporterTest extends KernelTestCase
{
    private string $dossier;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->dossier = self::getContainer()->getParameter('kernel.project_dir') . '/var/test-rulebook';
        array_map('unlink', glob($this->dossier . '/*.md') ?: []);
    }

    private function donnees(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $xp = (new RulebookDomaine())
            ->setCode('xp')
            ->setTitre('Expérience')
            ->setPosition(1);
        $em->persist($xp);
        $em->persist(
            (new RulebookRegle())
                ->setDomaine($xp)
                ->setCode('XP-01')
                ->setTitre('Coût')
                ->setStatut(RulebookStatut::BUGUE)
                ->setTexteClair('Clair.')
                ->setDetailTechnique('Technique.')
                ->setTestsRefs(['tests/XpTest.php'])
                ->setPosition(1),
        );
        $em->persist(
            (new RulebookRegle())
                ->setDomaine($xp)
                ->setCode('XP-02')
                ->setTitre('Vide')
                ->setPosition(2),
        );
        $em->flush();
    }

    public function testEcritUnFichierParDomaineEtUnIndex(): void
    {
        $this->donnees();
        $fichiers = self::getContainer()->get(RulebookExporter::class)->exporter();

        $this->assertFileExists($this->dossier . '/xp.md');
        $this->assertFileExists($this->dossier . '/index.md');
        $this->assertContains($this->dossier . '/xp.md', $fichiers);

        $contenu = (string) file_get_contents($this->dossier . '/xp.md');
        $this->assertStringContainsString('## XP-01 - Coût', $contenu);
        $this->assertStringContainsString('tests : tests/XpTest.php', $contenu);
        $this->assertStringContainsString("### Version claire\n\nClair.", $contenu);
        $this->assertStringContainsString('_Pas encore rédigée._', $contenu);
    }

    public function testSupprimeLesFichiersObsoletes(): void
    {
        $this->donnees();
        if (!is_dir($this->dossier)) {
            mkdir($this->dossier, 0o775, true);
        }
        file_put_contents($this->dossier . '/ancien.md', 'x');

        self::getContainer()->get(RulebookExporter::class)->exporter();

        $this->assertFileDoesNotExist($this->dossier . '/ancien.md');
    }
}
