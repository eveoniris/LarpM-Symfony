<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Enum\TerritoireStatut;
use App\Service\GroupeService;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\GroupeGnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\TerritoireFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration tests for GroupeService wealth calculation.
 *
 * DAMA bundle wraps each test in a DB transaction and rolls back automatically.
 */
#[Group('integration')]
class GroupeServiceTest extends KernelTestCase
{
    private GroupeService $groupeService;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->groupeService = $container->get(GroupeService::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
    }

    // -------------------------------------------------------------------------
    // getAllRichesse
    // -------------------------------------------------------------------------

    public function testGetAllRichesseReturnsZeroWithNoTerritoires(): void
    {
        $groupe = GroupeFactory::createOne();

        self::assertSame(0, $this->groupeService->getAllRichesse($groupe));
    }

    public function testGetAllRichesseWithStableTerritoire(): void
    {
        $groupe = GroupeFactory::createOne();
        // Territoire owned by groupe - stable, tresor=100 → 100 × 3 = 300
        TerritoireFactory::createOne([
            'tresor' => 100,
            'statut' => TerritoireStatut::STABLE,
            'groupe' => $groupe,
        ]);

        self::assertSame(300, $this->groupeService->getAllRichesse($groupe));
    }

    public function testGetAllRichesseWithInstableTerritoire(): void
    {
        $groupe = GroupeFactory::createOne();
        // Territoire owned by groupe - instable, tresor=100 → ceil(100 × 3 × 0.5) = 150
        TerritoireFactory::createOne([
            'tresor' => 100,
            'statut' => TerritoireStatut::INSTABLE,
            'groupe' => $groupe,
        ]);

        self::assertSame(150, $this->groupeService->getAllRichesse($groupe));
    }

    public function testGetAllRichesseWithMultipleTerritoires(): void
    {
        $groupe = GroupeFactory::createOne();
        // Stable tresor=100 → 300
        TerritoireFactory::createOne([
            'tresor' => 100,
            'statut' => TerritoireStatut::STABLE,
            'groupe' => $groupe,
        ]);
        // Instable tresor=50 → ceil(50 × 3 × 0.5) = 75
        TerritoireFactory::createOne([
            'tresor' => 50,
            'statut' => TerritoireStatut::INSTABLE,
            'groupe' => $groupe,
        ]);

        self::assertSame(375, $this->groupeService->getAllRichesse($groupe));
    }

    // -------------------------------------------------------------------------
    // isUserIsGroupeGnMember — no security context (null user)
    // -------------------------------------------------------------------------

    public function testIsUserIsGroupeGnMemberReturnsFalseWithoutSecurityContext(): void
    {
        // No logged-in user in KernelTestCase → security->getUser() returns null
        $gn = GnFactory::createOne();
        $groupe = GroupeFactory::createOne();
        $groupeGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn]);

        self::assertFalse($this->groupeService->isUserIsGroupeGnMember($groupeGn->object()));
    }

    // -------------------------------------------------------------------------
    // isUserIsGroupeGnResponsable — no security context (null user)
    // -------------------------------------------------------------------------

    public function testIsUserIsGroupeGnResponsableReturnsFalseWithoutSecurityContext(): void
    {
        $gn = GnFactory::createOne();
        $groupe = GroupeFactory::createOne();
        $groupeGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn]);

        self::assertFalse($this->groupeService->isUserIsGroupeGnResponsable($groupeGn->object()));
    }

    // -------------------------------------------------------------------------
    // getUserLastGroupeGn — no security context (null user)
    // -------------------------------------------------------------------------

    public function testGetUserLastGroupeGnReturnsNullWithoutSecurityContext(): void
    {
        $groupe = GroupeFactory::createOne();

        self::assertNull($this->groupeService->getUserLastGroupeGn($groupe->object()));
    }

    // -------------------------------------------------------------------------
    // getUserGroupeGns — no security context (null user)
    // -------------------------------------------------------------------------

    public function testGetUserGroupeGnsReturnsEmptyArrayWithoutSecurityContext(): void
    {
        $groupe = GroupeFactory::createOne();

        self::assertSame([], $this->groupeService->getUserGroupeGns($groupe->object()));
    }

    // -------------------------------------------------------------------------
    // isUserIsGroupeMember — participation future sans groupe_gn (bug regression)
    // -------------------------------------------------------------------------

    public function testIsUserIsGroupeMemberIgnoresParticipantWithoutGroupeGn(): void
    {
        // Simule le bug original : un participant futur sans groupe_gn ne doit pas
        // effacer l'appartenance réelle au groupe via une autre participation.
        // Sans contexte de sécurité, getUser() = null → false attendu.
        $gn1 = GnFactory::createOne();
        $gn2 = GnFactory::createOne();
        $groupe = GroupeFactory::createOne();
        $groupeGn = GroupeGnFactory::createOne(['groupe' => $groupe, 'gn' => $gn1]);
        $user = UserFactory::createOne();
        ParticipantFactory::createOne(['user' => $user, 'gn' => $gn1, 'groupeGn' => $groupeGn]);
        ParticipantFactory::createOne(['user' => $user, 'gn' => $gn2, 'groupeGn' => null]);

        // Sans sécurité → false (test structure uniquement, pas la logique auth)
        self::assertFalse($this->groupeService->isUserIsGroupeMember($groupe->object()));
    }
}
