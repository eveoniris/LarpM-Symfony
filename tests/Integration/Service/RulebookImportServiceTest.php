<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\RulebookDomaine;
use App\Entity\RulebookRegle;
use App\Entity\RulebookRevision;
use App\Enum\RulebookStatut;
use App\Service\Rulebook\RulebookImportService;
use App\Service\Rulebook\SpecParser;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Import initial du rulebook depuis la spec. DAMA annule chaque test à la fin.
 */
#[Group('integration')]
class RulebookImportServiceTest extends KernelTestCase
{
    private const string SPEC = <<<'MD'
        ## Territoire — Richesse (`Territoire::getRichesse()`)

        ### Formule
        ceil(tresor / 2)

        ### Statut
        ✅ VALIDE

        ### Tests
        → `TerritoireWealthTest::testRichesse*`

        ## Territoire — Défense

        ### Statut
        🐛 BUGUÉ

        ## XP — Coût

        ### Statut
        ✅ VALIDE
        MD;

    private RulebookImportService $service;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(RulebookImportService::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    /** @return list<\App\Service\Rulebook\RegleImportee> */
    private function regles(): array
    {
        return (new SpecParser())->parser(self::SPEC);
    }

    public function testSimulationNEnregistreRien(): void
    {
        $lignes = $this->service->importer($this->regles(), appliquer: false);
        $this->entityManager->clear();

        $this->assertCount(3, $lignes);
        $this->assertSame(['TERR-01', 'TERR-02', 'XP-01'], array_map(static fn ($ligne) => $ligne->code, $lignes));
        $this->assertSame(0, $this->entityManager->getRepository(RulebookRegle::class)->count([]));
        $this->assertSame(0, $this->entityManager->getRepository(RulebookDomaine::class)->count([]));
    }

    public function testImportCreeDomainesReglesEtRevisions(): void
    {
        $this->service->importer($this->regles(), appliquer: true);
        $this->entityManager->clear();

        $this->assertSame(2, $this->entityManager->getRepository(RulebookDomaine::class)->count([]));
        $this->assertSame(3, $this->entityManager->getRepository(RulebookRegle::class)->count([]));
        $this->assertSame(3, $this->entityManager->getRepository(RulebookRevision::class)->count([]));

        $regle = $this->entityManager->getRepository(RulebookRegle::class)->findOneBy(['code' => 'TERR-01']);
        $this->assertInstanceOf(RulebookRegle::class, $regle);
        $this->assertSame('Richesse', $regle->getTitre());
        $this->assertSame('Territoire', $regle->getDomaine()->getTitre());
        $this->assertSame(RulebookStatut::VALIDE, $regle->getStatut());
        $this->assertSame(['TerritoireWealthTest::testRichesse*'], $regle->getTestsRefs());
        $this->assertFalse($regle->isPubliee());
        $this->assertNull($regle->getTexteClair());
        $this->assertStringContainsString('ceil(tresor / 2)', (string) $regle->getDetailTechnique());
        $this->assertSame(RulebookStatut::BUGUE, $this->entityManager->getRepository(RulebookRegle::class)->findOneBy(['code' => 'TERR-02'])?->getStatut());
    }

    public function testRelanceNeDuppliquePasEtNeRemplacePasLesEditions(): void
    {
        $this->service->importer($this->regles(), appliquer: true);

        $regle = $this->entityManager->getRepository(RulebookRegle::class)->findOneBy(['code' => 'TERR-01']);
        $regle?->setTexteClair('Version claire rédigée par l\'équipe règle.');
        $this->entityManager->flush();
        $this->entityManager->clear();

        $lignes = $this->service->importer($this->regles(), appliquer: true);
        $this->entityManager->clear();

        $this->assertSame([false, false, false], array_map(static fn ($ligne) => $ligne->creee, $lignes));
        $this->assertSame(3, $this->entityManager->getRepository(RulebookRegle::class)->count([]));
        $this->assertSame('Version claire rédigée par l\'équipe règle.', $this->entityManager->getRepository(RulebookRegle::class)->findOneBy(['code' => 'TERR-01'])?->getTexteClair());
    }

    public function testNouvelleRegleDansUnDomaineExistantContinueLaNumerotation(): void
    {
        $this->service->importer($this->regles(), appliquer: true);
        $this->entityManager->clear();

        $plusUne = (new SpecParser())->parser(self::SPEC . "\n\n## Territoire — Stabilité\n\n### Statut\n✅ VALIDE\n");
        $lignes = $this->service->importer($plusUne, appliquer: true);

        $this->assertSame(['TERR-01', 'TERR-02', 'XP-01', 'TERR-03'], array_map(static fn ($ligne) => $ligne->code, $lignes));
        $this->assertSame([false, false, false, true], array_map(static fn ($ligne) => $ligne->creee, $lignes));
    }
}
