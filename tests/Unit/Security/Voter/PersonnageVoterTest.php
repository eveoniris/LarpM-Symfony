<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\EtatCivil;
use App\Entity\User;
use App\Security\Voter\PersonnageVoter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[Group('unit')]
class PersonnageVoterTest extends TestCase
{
    private function makeToken(?User $user): TokenInterface
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    private function makeUser(bool $complet): User
    {
        $user = $this->createStub(User::class);
        $user->method('isEtatCivilComplet')->willReturn($complet);

        return $user;
    }

    /** @return array<string, array{bool}> */
    public static function dataProvider(): array
    {
        return [
            'complet' => [true],
            'incomplet' => [false],
        ];
    }

    #[DataProvider('dataProvider')]
    public function testPersonnageCreate(bool $complet): void
    {
        $voter = new PersonnageVoter();
        $user = $this->makeUser($complet);
        $token = $this->makeToken($user);

        $expected = $complet ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED;
        $this->assertSame($expected, $voter->vote($token, null, [PersonnageVoter::PERSONNAGE_CREATE]));
    }

    public function testPersonnageCreateWithAnonymous(): void
    {
        $voter = new PersonnageVoter();
        $token = $this->makeToken(null);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [PersonnageVoter::PERSONNAGE_CREATE]));
    }

    public function testSupportsOnlyCreate(): void
    {
        $voter = new PersonnageVoter();
        $user = $this->makeUser(true);
        $token = $this->makeToken($user);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, null, ['OTHER_ATTRIBUTE']));
    }
}
