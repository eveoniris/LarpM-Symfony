<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Enum\GnTimelineVisibilite;
use App\Enum\Role;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GnTimelineVisibiliteTest extends TestCase
{
    /** @return iterable<string, array{GnTimelineVisibilite, list<Role>, bool}> */
    public static function cas(): iterable
    {
        yield 'public : un joueur sans rôle' => [GnTimelineVisibilite::PUBLIC, [], true];
        yield 'secret : un joueur sans rôle' => [GnTimelineVisibilite::SECRET, [], false];
        yield 'secret : scénariste' => [GnTimelineVisibilite::SECRET, [Role::SCENARISTE], true];
        yield 'secret : wargame' => [GnTimelineVisibilite::SECRET, [Role::WARGAME], true];
        yield 'secret : stock seul' => [GnTimelineVisibilite::SECRET, [Role::STOCK], false];
        yield 'technique : scénariste' => [GnTimelineVisibilite::TECHNIQUE, [Role::SCENARISTE], false];
        yield 'technique : admin' => [GnTimelineVisibilite::TECHNIQUE, [Role::ADMIN], true];
    }

    /** @param list<Role> $roles */
    #[DataProvider('cas')]
    public function testVisibiliteSelonLesRoles(GnTimelineVisibilite $visibilite, array $roles, bool $attendu): void
    {
        $aLeRole = static fn (Role $role): bool => \in_array($role, $roles, strict: true);

        $this->assertSame($attendu, $visibilite->estVisible($aLeRole));
    }
}
