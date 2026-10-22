<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\CompetenceFamily;
use App\Entity\Gn;
use App\Entity\Personnage;
use App\Entity\PersonnageTrigger;
use App\Enum\CompetenceFamilyType;
use App\Enum\TriggerType;
use App\Service\CompetenceService;
use App\Service\PersonnageService;
use App\Tests\Factory\CompetenceFactory;
use App\Tests\Factory\CompetenceFamilyFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\LevelFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Littérature : sort et potion donnés à l'achat du niveau, puis par GN joué sans cumul au départ.
 *
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 */
#[Group('integration')]
class LitteratureTriggersTest extends KernelTestCase
{
    private CompetenceService $competenceService;
    private PersonnageService $personnageService;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->competenceService = $container->get(CompetenceService::class);
        $this->personnageService = $container->get(PersonnageService::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);

        // les requêtes par GN filtrent sur l'id fixe de la famille, absent de la base de test
        $familyId = CompetenceFamilyType::LITERATURE->getId();
        if (!$this->entityManager->find(CompetenceFamily::class, $familyId) instanceof CompetenceFamily) {
            $created = CompetenceFamilyFactory::createOne(['label' => 'Littérature']);
            $this->entityManager->getConnection()->executeStatement('UPDATE competence_family SET id = :id WHERE id = :old', ['id' => $familyId, 'old' => $created->getId()]);
            $this->entityManager->clear();
        }
    }

    private function buy(Personnage $personnage, int $levelIndex): void
    {
        $family = $this->entityManager->find(CompetenceFamily::class, CompetenceFamilyType::LITERATURE->getId());

        $level = LevelFactory::createOne(['index' => $levelIndex]);
        $competence = CompetenceFactory::createOne(['competenceFamily' => $family, 'level' => $level]);

        $service = $this->competenceService->getCompetenceService($competence);
        $service->setPersonnage($personnage);
        $service->addCompetence(0);
    }

    /** @return list<string> tags des triggers, actifs ou non */
    private function tags(Personnage $personnage): array
    {
        $this->entityManager->refresh($personnage);

        // les triggers de langue donnés à l'achat ne nous intéressent pas ici
        $tags = array_map(static fn (PersonnageTrigger $trigger): string => $trigger->getTag()->value, $personnage->getAllPersonnageTriggers()->toArray());

        return array_values(array_filter($tags, static fn (string $tag): bool => str_starts_with($tag, 'SORT') || str_starts_with($tag, 'ALCHIMIE')));
    }

    private function lock(Gn $gn): void
    {
        foreach ($this->personnageService->lockGnGiveLiteratureGnBonus($gn, true) as $_) {
        }
        $this->entityManager->flush();
    }

    public function testAchatDonneSortEtPotionDuNiveau(): void
    {
        $personnage = PersonnageFactory::createOne(['xp' => 1000]);
        $this->buy($personnage, 2);

        $tags = $this->tags($personnage);
        self::assertContains(TriggerType::SORT_APPRENTI->value, $tags);
        self::assertContains(TriggerType::ALCHIMIE_APPRENTI->value, $tags);
    }

    public function testApprentiNeDonnePasDeSortNiPotion(): void
    {
        $personnage = PersonnageFactory::createOne(['xp' => 1000]);
        $this->buy($personnage, 1);

        self::assertNotContains(TriggerType::SORT_APPRENTI->value, $this->tags($personnage));
    }

    public function testPremierGnJoueEstAbsorbeParLAchat(): void
    {
        $personnage = PersonnageFactory::createOne(['xp' => 1000]);
        $this->buy($personnage, 2);
        $gn1 = GnFactory::createOne();
        ParticipantFactory::createOne(['gn' => $gn1, 'personnage' => $personnage, 'user' => UserFactory::createOne()]);

        $this->lock($gn1);

        self::assertCount(2, $this->tags($personnage), 'le premier GN ne cumule pas avec le trigger d\'achat');
        $this->lock($gn1);
        self::assertCount(2, $this->tags($personnage), 'idempotent');
    }

    public function testGnSuivantDonneUnNouveauChoix(): void
    {
        $personnage = PersonnageFactory::createOne(['xp' => 1000]);
        $this->buy($personnage, 2);
        $gn1 = GnFactory::createOne();
        $gn2 = GnFactory::createOne();
        ParticipantFactory::createOne(['gn' => $gn1, 'personnage' => $personnage, 'user' => UserFactory::createOne()]);
        ParticipantFactory::createOne(['gn' => $gn2, 'personnage' => $personnage, 'user' => UserFactory::createOne()]);

        $this->lock($gn1);
        $this->lock($gn2);

        self::assertCount(4, $this->tags($personnage));
    }

    public function testAchatConsommeAbsorbeAussiLePremierGn(): void
    {
        $personnage = PersonnageFactory::createOne(['xp' => 1000]);
        $this->buy($personnage, 2);
        $this->entityManager->refresh($personnage);
        foreach ($personnage->getPersonnageTriggers() as $trigger) {
            $trigger->consume('choix fait en inter-jeu');
        }
        $this->entityManager->flush();

        $gn1 = GnFactory::createOne();
        ParticipantFactory::createOne(['gn' => $gn1, 'personnage' => $personnage, 'user' => UserFactory::createOne()]);
        $this->lock($gn1);

        self::assertCount(2, $this->tags($personnage));
    }

    public function testNonInscritNeRecoitRienAuLock(): void
    {
        $personnage = PersonnageFactory::createOne(['xp' => 1000]);
        $this->buy($personnage, 2);
        $gn1 = GnFactory::createOne();

        $this->lock($gn1);

        self::assertCount(2, $this->tags($personnage));
    }
}
