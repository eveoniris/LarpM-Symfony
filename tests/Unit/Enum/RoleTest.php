<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\Role;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    public function testStatistiqueRoleExists(): void
    {
        // On compare via le tableau des valeurs : une comparaison directe contre
        // Role::STATISTIQUE->value serait jugée triviale par PHPStan, alors que
        // c'est précisément la chaîne stockée en base qu'on veut figer ici.
        $values = array_map(static fn (Role $role): string => $role->value, Role::cases());

        static::assertContains('ROLE_STATISTIQUE', $values);
    }

    public function testStatistiqueRoleHasALabel(): void
    {
        static::assertNotSame('', Role::STATISTIQUE->getLabel());
        static::assertNotSame(Role::STATISTIQUE->value, Role::STATISTIQUE->getLabel());
    }

    /**
     * Chaque cas de l'enum doit avoir un libellé : sans cela, getLabel() retombe
     * sur la valeur brute du rôle et l'écran des droits affiche « ROLE_XXX ».
     */
    #[DataProvider('provideEveryRole')]
    public function testEveryRoleHasALabel(Role $role): void
    {
        static::assertArrayHasKey($role->value, Role::getLabels());
        static::assertNotSame('', Role::getLabels()[$role->value]);
    }

    public function testEveryLabelMatchesAnExistingRole(): void
    {
        $values = array_map(static fn (Role $role): string => $role->value, Role::cases());

        foreach (array_keys(Role::getLabels()) as $value) {
            static::assertContains($value, $values);
        }
    }

    /** @return iterable<string, array{Role}> */
    public static function provideEveryRole(): iterable
    {
        foreach (Role::cases() as $role) {
            yield $role->name => [$role];
        }
    }
}
