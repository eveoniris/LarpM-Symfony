<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Groupe;
use App\Entity\GroupeGn;
use App\Enum\TerritoireStatut;
use App\Service\GroupeService;
use App\Tests\Factory\GroupeFactory;
use App\Tests\Factory\TerritoireFactory;
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
        // No logged-in user in KernelTestCase → security->getUser() returns null immediately.
        // The entity is never accessed, so a bare new() is enough.
        self::assertFalse($this->groupeService->isUserIsGroupeGnMember(new GroupeGn()));
    }

    // -------------------------------------------------------------------------
    // isUserIsGroupeGnResponsable - no security context (null user)
    // -------------------------------------------------------------------------

    public function testIsUserIsGroupeGnResponsableReturnsFalseWithoutSecurityContext(): void
    {
        self::assertFalse($this->groupeService->isUserIsGroupeGnResponsable(new GroupeGn()));
    }

    // -------------------------------------------------------------------------
    // getUserLastGroupeGn - no security context (null user)
    // -------------------------------------------------------------------------

    public function testGetUserLastGroupeGnReturnsNullWithoutSecurityContext(): void
    {
        self::assertNull($this->groupeService->getUserLastGroupeGn(new Groupe()));
    }

    // -------------------------------------------------------------------------
    // getUserGroupeGns - no security context (null user)
    // -------------------------------------------------------------------------

    public function testGetUserGroupeGnsReturnsEmptyArrayWithoutSecurityContext(): void
    {
        self::assertSame([], $this->groupeService->getUserGroupeGns(new Groupe()));
    }

    // -------------------------------------------------------------------------
    // isUserIsGroupeMember - no security context (null user)
    // -------------------------------------------------------------------------

    public function testIsUserIsGroupeMemberReturnsFalseWithoutSecurityContext(): void
    {
        self::assertFalse($this->groupeService->isUserIsGroupeMember(new Groupe()));
    }
}
