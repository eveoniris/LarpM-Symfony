<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Gn;
use App\Service\StatsService;
use App\Tests\Factory\AgeFactory;
use App\Tests\Factory\BilletFactory;
use App\Tests\Factory\ClasseFactory;
use App\Tests\Factory\CompetenceFactory;
use App\Tests\Factory\CompetenceFamilyFactory;
use App\Tests\Factory\ExperienceGainFactory;
use App\Tests\Factory\GenreFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\LevelFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\TerritoireFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration tests for StatsService::getPersonnagesExtract().
 *
 * Couvre le SQL natif : pivot par famille de compétences, XP total, nombre de
 * morts par joueur, détection PNJ sur la dernière participation, libellé de
 * classe selon le genre, origine et filtre GN optionnel.
 *
 * DAMA bundle (configured in phpunit.dist.xml) wraps each test in a DB transaction
 * and rolls back automatically - no need for the Foundry ResetDatabase trait.
 */
#[Group('integration')]
class StatsServicePersonnagesTest extends KernelTestCase
{
    private StatsService $statsService;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->statsService = $container->get(StatsService::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
    }

    /** @return array<int|string, array<string, mixed>> */
    private function extract(?Gn $gn = null): array
    {
        $rows = $this->statsService->getPersonnagesExtract($gn)->getResult();
        $byId = [];
        foreach ($rows as $row) {
            $byId[$row['personnageId']] = $row;
        }

        return $byId;
    }

    public function testExtractReturnsOneRowPerPersonnage(): void
    {
        $p1 = PersonnageFactory::createOne(['nom' => 'Alpha']);
        $p2 = PersonnageFactory::createOne(['nom' => 'Bravo']);

        $rows = $this->extract();

        static::assertArrayHasKey($p1->getId(), $rows);
        static::assertArrayHasKey($p2->getId(), $rows);
        static::assertSame('Alpha', $rows[$p1->getId()]['nom']);
        static::assertSame('Bravo', $rows[$p2->getId()]['nom']);
    }

    public function testEveryCompetenceFamilyHasAColumn(): void
    {
        CompetenceFamilyFactory::createOne();
        PersonnageFactory::createOne();

        $rows = $this->extract();
        $row = reset($rows);

        static::assertIsArray($row);
        foreach ($this->statsService->getPersonnagesExtractFamilies() as $family) {
            static::assertArrayHasKey('fam_' . $family->getId(), $row);
        }
    }

    public function testBestLevelOfTheFamilyIsUsedAndMissingFamilyIsZero(): void
    {
        $family = CompetenceFamilyFactory::createOne();
        $otherFamily = CompetenceFamilyFactory::createOne();
        $personnage = PersonnageFactory::createOne();

        // Competence::$personnages est le côté propriétaire du ManyToMany
        // (Personnage::$competences est mappedBy) : c'est donc par là qu'on
        // rattache les compétences pour que la table personnages_competences
        // soit alimentée.
        // Deux compétences de la même famille : c'est le meilleur niveau qui compte.
        CompetenceFactory::createOne([
            'competenceFamily' => $family,
            'level' => LevelFactory::createOne(['index' => 2]),
        ])->addPersonnage($personnage);
        CompetenceFactory::createOne([
            'competenceFamily' => $family,
            'level' => LevelFactory::createOne(['index' => 5]),
        ])->addPersonnage($personnage);
        CompetenceFactory::createOne([
            'competenceFamily' => $otherFamily,
            'level' => LevelFactory::createOne(['index' => 1]),
        ])->addPersonnage($personnage);
        $this->entityManager->flush();

        $row = $this->extract()[$personnage->getId()];

        static::assertSame(5, $row['fam_' . $family->getId()]);
        static::assertSame(1, $row['fam_' . $otherFamily->getId()]);
    }

    public function testPersonnageWithoutCompetenceHasZeroEverywhere(): void
    {
        $family = CompetenceFamilyFactory::createOne();
        $personnage = PersonnageFactory::createOne();

        static::assertSame(0, $this->extract()[$personnage->getId()]['fam_' . $family->getId()]);
    }

    public function testXpTotalExcludesSuppressionDeCompetence(): void
    {
        $personnage = PersonnageFactory::createOne();

        ExperienceGainFactory::createOne(['personnage' => $personnage, 'xp_gain' => 30, 'explanation' => 'Gain classique']);
        ExperienceGainFactory::createOne([
            'personnage' => $personnage,
            'xp_gain' => 70,
            'explanation' => 'Suppression de la compétence X',
        ]);

        $row = $this->extract()[$personnage->getId()];

        static::assertSame(30, $row['xpTotal']);
        // Cohérence avec le calcul métier de l'entité.
        static::assertSame(30, $personnage->getXpTotal());
    }

    public function testXpTotalIsZeroWithoutGain(): void
    {
        $personnage = PersonnageFactory::createOne();

        static::assertSame(0, $this->extract()[$personnage->getId()]['xpTotal']);
    }

    public function testDeadCharactersAreCountedPerPlayerAndRepeatedOnEachRow(): void
    {
        $user = UserFactory::createOne();
        $vivant = PersonnageFactory::createOne(['user' => $user, 'vivant' => true, 'nom' => 'Vivant']);
        $mort1 = PersonnageFactory::createOne(['user' => $user, 'vivant' => false, 'nom' => 'Mort1']);
        $mort2 = PersonnageFactory::createOne(['user' => $user, 'vivant' => false, 'nom' => 'Mort2']);
        $autreJoueur = PersonnageFactory::createOne(['vivant' => false, 'nom' => 'Mort ailleurs']);

        $rows = $this->extract();

        static::assertSame(2, $rows[$vivant->getId()]['nbPersosMortsJoueur']);
        static::assertSame(2, $rows[$mort1->getId()]['nbPersosMortsJoueur']);
        static::assertSame(2, $rows[$mort2->getId()]['nbPersosMortsJoueur']);
        // Son propre joueur n'a qu'un personnage mort : le sien.
        static::assertSame(1, $rows[$autreJoueur->getId()]['nbPersosMortsJoueur']);
        static::assertNotSame($user->getId(), $rows[$autreJoueur->getId()]['userId']);
    }

    public function testDeadCountIgnoresDeadCharactersWithoutPlayer(): void
    {
        $vivant = PersonnageFactory::createOne(['vivant' => true]);
        PersonnageFactory::createOne(['vivant' => false, 'user' => null]);

        static::assertSame(0, $this->extract()[$vivant->getId()]['nbPersosMortsJoueur']);
    }

    public function testPnjIsReadFromTheMostRecentParticipation(): void
    {
        $joueur = UserFactory::createOne();
        $personnage = PersonnageFactory::createOne();
        $gnAncien = GnFactory::createOne();
        $gnRecent = GnFactory::createOne();

        ParticipantFactory::createOne([
            'personnage' => $personnage,
            'user' => $joueur,
            'gn' => $gnAncien,
            'billet' => BilletFactory::createOne(['label' => 'Joueur standard', 'gn' => $gnAncien]),
        ]);
        $billetPnj = BilletFactory::createOne(['label' => 'Billet PNJ', 'gn' => $gnRecent]);
        ParticipantFactory::createOne([
            'personnage' => $personnage,
            'user' => $joueur,
            'gn' => $gnRecent,
            'billet' => $billetPnj,
        ]);

        static::assertTrue($billetPnj->isPnj());
        static::assertTrue((bool) $this->extract()[$personnage->getId()]['pnj']);
    }

    public function testPnjIsFalseWithAPnjTicketOnAnOlderParticipation(): void
    {
        $joueur = UserFactory::createOne();
        $personnage = PersonnageFactory::createOne();
        $gnAncien = GnFactory::createOne();
        $gnRecent = GnFactory::createOne();

        ParticipantFactory::createOne([
            'personnage' => $personnage,
            'user' => $joueur,
            'gn' => $gnAncien,
            'billet' => BilletFactory::createOne(['label' => 'Billet PNJ', 'gn' => $gnAncien]),
        ]);
        ParticipantFactory::createOne([
            'personnage' => $personnage,
            'user' => $joueur,
            'gn' => $gnRecent,
            'billet' => BilletFactory::createOne(['label' => 'Joueur standard', 'gn' => $gnRecent]),
        ]);

        static::assertFalse((bool) $this->extract()[$personnage->getId()]['pnj']);
    }

    public function testPnjIsFalseWithoutParticipation(): void
    {
        $personnage = PersonnageFactory::createOne();

        static::assertFalse((bool) $this->extract()[$personnage->getId()]['pnj']);
    }

    public function testClasseUsesMasculinLabelForMasculinGenre(): void
    {
        $classe = ClasseFactory::createOne(['label_masculin' => 'Chevalier', 'label_feminin' => 'Chevalière']);
        $personnage = PersonnageFactory::createOne([
            'classe' => $classe,
            'genre' => GenreFactory::createOne(['label' => 'Masculin']),
        ]);

        static::assertSame('Chevalier', $this->extract()[$personnage->getId()]['classe']);
    }

    public function testClasseUsesFemininLabelForOtherGenres(): void
    {
        $classe = ClasseFactory::createOne(['label_masculin' => 'Sorcier', 'label_feminin' => 'Sorcière']);
        $personnage = PersonnageFactory::createOne([
            'classe' => $classe,
            'genre' => GenreFactory::createOne(['label' => 'Féminin']),
        ]);

        static::assertSame('Sorcière', $this->extract()[$personnage->getId()]['classe']);
    }

    public function testClasseUsesMasculinLabelWhenGenreLabelIsMasculin(): void
    {
        // Casse explicitement le libellé : GenreFactory génère des mots aléatoires,
        // or le libellé de classe dépend du genre (cf. Classe::getLabelPourGenre()).
        $classe = ClasseFactory::createOne(['label_masculin' => 'Barbare', 'label_feminin' => 'Barbare']);
        $personnage = PersonnageFactory::createOne([
            'classe' => $classe,
            'genre' => GenreFactory::createOne(['label' => 'Masculin']),
        ]);

        static::assertSame('Barbare', $this->extract()[$personnage->getId()]['classe']);
    }

    public function testOriginAgeAndRenommeAreExposed(): void
    {
        $territoire = TerritoireFactory::createOne(['nom' => 'Terre de Feu']);
        $age = AgeFactory::createOne(['label' => 'Adulte']);
        $personnage = PersonnageFactory::createOne([
            'territoire' => $territoire,
            'age' => $age,
            'age_reel' => 34,
            'renomme' => 7,
        ]);

        $row = $this->extract()[$personnage->getId()];

        static::assertSame('Terre de Feu', $row['origine']);
        static::assertSame('Adulte', $row['age']);
        static::assertSame(34, (int) $row['ageReel']);
        static::assertSame(7, $row['renomme']);
    }

    public function testRenommeIsZeroWhenNotSet(): void
    {
        // BasePersonnage::setRenomme() n'accepte pas null et la colonne est
        // nullable : un personnage créé sans renommée l'a donc à NULL en base,
        // ce que l'extract doit exposer comme 0.
        $personnage = PersonnageFactory::createOne();

        static::assertSame(0, $this->extract()[$personnage->getId()]['renomme']);
    }

    public function testGnFilterRestrictsToParticipantsOfThatGn(): void
    {
        $joueur = UserFactory::createOne();
        $gn = GnFactory::createOne();
        $autreGn = GnFactory::createOne();

        $dansLeGn = PersonnageFactory::createOne(['nom' => 'Present']);
        $horsGn = PersonnageFactory::createOne(['nom' => 'Absent']);

        ParticipantFactory::createOne(['personnage' => $dansLeGn, 'user' => $joueur, 'gn' => $gn]);
        ParticipantFactory::createOne(['personnage' => $horsGn, 'user' => $joueur, 'gn' => $autreGn]);

        $rows = $this->extract($gn);

        static::assertArrayHasKey($dansLeGn->getId(), $rows);
        static::assertArrayNotHasKey($horsGn->getId(), $rows);
    }

    public function testWithoutGnFilterAllPersonnagesAreReturned(): void
    {
        $joueur = UserFactory::createOne();
        $gn = GnFactory::createOne();
        ParticipantFactory::createOne([
            'personnage' => PersonnageFactory::createOne(['nom' => 'AvecGN']),
            'user' => $joueur,
            'gn' => $gn,
        ]);
        PersonnageFactory::createOne(['nom' => 'SansGN']);

        static::assertCount(2, $this->extract());
    }

    public function testGnFilterDoesNotDuplicatePersonnageParticipatingTwice(): void
    {
        $joueur = UserFactory::createOne();
        $gn = GnFactory::createOne();
        $personnage = PersonnageFactory::createOne();

        ParticipantFactory::createOne(['personnage' => $personnage, 'user' => $joueur, 'gn' => $gn]);
        ParticipantFactory::createOne(['personnage' => $personnage, 'user' => $joueur, 'gn' => $gn]);

        static::assertCount(1, $this->extract($gn));
    }

    public function testPersonnageWithoutUserDoesNotBreakTheExtract(): void
    {
        $personnage = PersonnageFactory::createOne(['user' => null]);

        $row = $this->extract()[$personnage->getId()];

        static::assertNull($row['userId']);
        static::assertNull($row['email']);
        static::assertSame(0, $row['nbPersosMortsJoueur']);
    }

    public function testEmailOfThePlayerIsExposed(): void
    {
        $user = UserFactory::createOne(['email' => 'joueur@example.com']);
        $personnage = PersonnageFactory::createOne(['user' => $user]);

        $row = $this->extract()[$personnage->getId()];

        static::assertSame($user->getId(), $row['userId']);
        static::assertSame('joueur@example.com', $row['email']);
    }
}
