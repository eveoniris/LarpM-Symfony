<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\RulebookRegle;
use App\Entity\User;
use App\Enum\Role;
use App\Enum\RulebookStatut;
use App\Security\Voter\RulebookVoter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Règles d'accès au rulebook : scénaristes en lecture de la version claire, équipe règle en édition, admin sur les domaines.
 */
// @mago-expect lint:too-many-methods
#[Group('unit')]
class RulebookVoterTest extends TestCase
{
    /** @param list<Role> $roles rôles effectivement accordés (l'héritage de ROLE_ADMIN est simulé par l'appelant) */
    private function vote(array $roles, string $attribute, ?RulebookRegle $regle = null, bool $connecte = true): int
    {
        $accordes = array_map(static fn (Role $role): string => $role->value, $roles);
        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturnCallback(static fn (string $role): bool => \in_array($role, $accordes, strict: true));

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($connecte ? $this->createStub(User::class) : null);

        return (new RulebookVoter($security))->vote($token, $regle, [$attribute]);
    }

    private function regle(RulebookStatut $statut = RulebookStatut::VALIDE, bool $publiee = true, ?string $texteClair = 'Texte clair.'): RulebookRegle
    {
        return (new RulebookRegle())
            ->setStatut($statut)
            ->setPubliee($publiee)
            ->setTexteClair($texteClair);
    }

    public function testAnonymeRefuse(): void
    {
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote([Role::REGLE, Role::SCENARISTE], RulebookVoter::ACCESS, connecte: false));
    }

    public function testAccesSansRoleRefuse(): void
    {
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote([Role::USER], RulebookVoter::ACCESS));
    }

    #[DataProvider('rolesAvecAcces')]
    public function testAccesAutorise(Role $role): void
    {
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote([$role], RulebookVoter::ACCESS));
    }

    /** @return array<string, array{Role}> */
    public static function rolesAvecAcces(): array
    {
        return ['scenariste' => [Role::SCENARISTE], 'regle' => [Role::REGLE]];
    }

    public function testScenaristeVoitRegleValidePubliee(): void
    {
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote([Role::SCENARISTE], RulebookVoter::VIEW, $this->regle()));
    }

    #[DataProvider('regleInvisibleDuScenariste')]
    public function testScenaristeNeVoitPasLesReglesReservees(RulebookStatut $statut, bool $publiee, ?string $texteClair): void
    {
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote([Role::SCENARISTE], RulebookVoter::VIEW, $this->regle($statut, $publiee, $texteClair)));
    }

    /** @return array<string, array{RulebookStatut, bool, ?string}> */
    public static function regleInvisibleDuScenariste(): array
    {
        return [
            'bugue' => [RulebookStatut::BUGUE, true, 'Texte.'],
            'a confirmer' => [RulebookStatut::A_CONFIRMER, true, 'Texte.'],
            'todo' => [RulebookStatut::TODO, true, 'Texte.'],
            'non publiee' => [RulebookStatut::VALIDE, false, 'Texte.'],
            'sans version claire' => [RulebookStatut::VALIDE, true, null],
            'version claire blanche' => [RulebookStatut::VALIDE, true, "  \n "],
        ];
    }

    #[DataProvider('statutsPublics')]
    public function testScenaristeVoitLesStatutsPublics(RulebookStatut $statut): void
    {
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote([Role::SCENARISTE], RulebookVoter::VIEW, $this->regle($statut)));
    }

    /** @return array<string, array{RulebookStatut}> */
    public static function statutsPublics(): array
    {
        return ['valide' => [RulebookStatut::VALIDE], 'fixe' => [RulebookStatut::FIXE], 'hardcode' => [RulebookStatut::HARDCODE]];
    }

    public function testEquipeRegleVoitToutMemeUnBrouillonBugue(): void
    {
        $regle = $this->regle(RulebookStatut::BUGUE, publiee: false, texteClair: null);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote([Role::REGLE], RulebookVoter::VIEW, $regle));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote([Role::REGLE], RulebookVoter::VIEW_TECHNIQUE, $regle));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote([Role::REGLE], RulebookVoter::EDIT, $regle));
    }

    public function testScenaristeNiDetailTechniqueNiEdition(): void
    {
        $regle = $this->regle();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote([Role::SCENARISTE], RulebookVoter::VIEW_TECHNIQUE, $regle));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote([Role::SCENARISTE], RulebookVoter::EDIT, $regle));
    }

    public function testGestionDesDomainesReserveeALAdmin(): void
    {
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote([Role::REGLE, Role::SCENARISTE], RulebookVoter::MANAGE_DOMAINES));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote([Role::ADMIN, Role::REGLE, Role::SCENARISTE], RulebookVoter::MANAGE_DOMAINES));
    }

    public function testNeSeProntoncePasSurUnAutreAttribut(): void
    {
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote([Role::ADMIN], 'AUTRE_ATTRIBUT'));
    }

    public function testAttributSurRegleSansSujetEstIgnore(): void
    {
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote([Role::ADMIN], RulebookVoter::EDIT));
    }
}
