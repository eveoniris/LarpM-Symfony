<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\Groupe;
use App\Entity\Territoire;
use App\Entity\User;
use App\Enum\Role;
use App\Security\Voter\TerritoireVoter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[Group('unit')]
class TerritoireVoterTest extends TestCase
{
    /**
     * @param array<string, bool> $grantedRoles rôles globaux accordés à l'utilisateur courant
     */
    private function makeVoter(array $grantedRoles = []): TerritoireVoter
    {
        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturnCallback(
            static fn (mixed $attribute): bool => $grantedRoles[$attribute] ?? false
        );

        return new TerritoireVoter($security);
    }

    private function makeUser(?int $id): User
    {
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn($id);

        return $user;
    }

    private function makeTerritoire(?User $scenariste): Territoire
    {
        $groupe = $this->createStub(Groupe::class);
        $groupe->method('getScenariste')->willReturn($scenariste);

        $territoire = $this->createStub(Territoire::class);
        $territoire->method('getGroupe')->willReturn($groupe);

        return $territoire;
    }

    private function makeToken(?User $user): TokenInterface
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    public function testOrgaIsGranted(): void
    {
        $voter = $this->makeVoter([Role::ORGA->value => true]);
        // Aucun scénariste : l'accès doit venir du rôle global uniquement.
        $territoire = $this->makeTerritoire(null);
        $token = $this->makeToken($this->makeUser(42));

        static::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($token, $territoire, [TerritoireVoter::EDIT_LANGUE])
        );
    }

    public function testCartographeIsGranted(): void
    {
        $voter = $this->makeVoter([Role::CARTOGRAPHE->value => true]);
        $territoire = $this->makeTerritoire(null);
        $token = $this->makeToken($this->makeUser(42));

        static::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($token, $territoire, [TerritoireVoter::EDIT_LANGUE])
        );
    }

    public function testScenaristeOfOwningGroupIsGranted(): void
    {
        $voter = $this->makeVoter(); // aucun rôle global
        $user = $this->makeUser(7);
        $territoire = $this->makeTerritoire($this->makeUser(7)); // même id que l'utilisateur
        $token = $this->makeToken($user);

        static::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($token, $territoire, [TerritoireVoter::EDIT_LANGUE])
        );
    }

    public function testScenaristeOfAnotherGroupIsDenied(): void
    {
        $voter = $this->makeVoter(); // aucun rôle global
        $user = $this->makeUser(7);
        $territoire = $this->makeTerritoire($this->makeUser(99)); // scénariste d'un autre groupe
        $token = $this->makeToken($user);

        static::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($token, $territoire, [TerritoireVoter::EDIT_LANGUE])
        );
    }

    public function testTerritoireWithoutScenaristeIsDenied(): void
    {
        $voter = $this->makeVoter(); // aucun rôle global
        $territoire = $this->makeTerritoire(null);
        $token = $this->makeToken($this->makeUser(7));

        static::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($token, $territoire, [TerritoireVoter::EDIT_LANGUE])
        );
    }

    public function testAbstainsOnUnsupportedAttribute(): void
    {
        $voter = $this->makeVoter([Role::ORGA->value => true]);
        $territoire = $this->makeTerritoire(null);
        $token = $this->makeToken($this->makeUser(7));

        static::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote($token, $territoire, ['SOME_OTHER_ATTRIBUTE'])
        );
    }
}
