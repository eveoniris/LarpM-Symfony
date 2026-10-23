<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Conception;
use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Enum\Role;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Droits sur un dépouillement, une conception d'héritier ou le GN : la validation (et le renvoi, la décision sur les héritiers) est réservée aux scénaristes, wargames et admins.
 */
class DepouillementVoter extends Voter
{
    public const string VALIDER = 'DEPOUILLEMENT_VALIDER';

    public function __construct(
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::VALIDER === $attribute && ($subject instanceof Depouillement || $subject instanceof Conception || $subject instanceof Gn);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, #[\SensitiveParameter] TokenInterface $token, ?Vote $vote = null): bool
    {
        foreach ([Role::SCENARISTE, Role::WARGAME, Role::ADMIN] as $role) {
            if ($this->security->isGranted($role->value)) {
                return true;
            }
        }

        return false;
    }
}
