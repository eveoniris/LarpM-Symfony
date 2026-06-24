<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Enum\Role;
use App\Service\CompetenceService;
use App\Service\ConditionsService;
use App\Service\DataFormatterService;
use App\Service\GroupeService;
use App\Service\PersonnageService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Tests unitaires de l'exemption "staff" sur la limite de personnages.
 *
 * isStaff() et la branche d'exemption de canCreatePersonnage() ne dépendent
 * que de Security : on les teste en isolation avec un Security mocké, sans DB.
 */
#[Group('unit')]
class PersonnageServiceStaffTest extends TestCase
{
    /**
     * @param list<string> $grantedRoles rôles globaux accordés à l'utilisateur courant
     */
    private function makeService(array $grantedRoles, ?EntityManagerInterface $entityManager = null): PersonnageService
    {
        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturnCallback(static fn (mixed $attribute): bool => \in_array($attribute, $grantedRoles, true));

        return new PersonnageService(
            $entityManager ?? $this->createStub(EntityManagerInterface::class),
            $this->createStub(ValidatorInterface::class),
            $this->createStub(FormFactoryInterface::class),
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(CompetenceService::class),
            $this->createStub(GroupeService::class),
            $security,
            $this->createStub(ConditionsService::class),
            $this->createStub(DataFormatterService::class),
            $this->createStub(LoggerInterface::class),
        );
    }

    public static function staffRolesProvider(): array
    {
        return [
            'admin' => [Role::ADMIN->value],
            'orga' => [Role::ORGA->value],
            'scenariste' => [Role::SCENARISTE->value],
            'gestion' => [Role::GESTION->value],
        ];
    }

    #[DataProvider('staffRolesProvider')]
    public function testIsStaffTrueForStaffRoles(string $role): void
    {
        static::assertTrue($this->makeService([$role])->isStaff());
    }

    public function testIsStaffFalseForRegularUser(): void
    {
        static::assertFalse($this->makeService([Role::USER->value])->isStaff());
    }

    public function testCanCreatePersonnageStaffBypassesLimitWithoutHittingDatabase(): void
    {
        // L'EntityManager ne doit JAMAIS être sollicité : le staff sort avant le comptage.
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(static::never())->method('getRepository');

        $service = $this->makeService([Role::SCENARISTE->value], $entityManager);

        static::assertTrue($service->canCreatePersonnage(new User()));
    }
}
