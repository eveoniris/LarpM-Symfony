<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PersonnageVoter extends Voter
{
    public const PERSONNAGE_CREATE = 'PERSONNAGE_CREATE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::PERSONNAGE_CREATE === $attribute;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?\Symfony\Component\Security\Core\Authorization\Voter\Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (self::PERSONNAGE_CREATE !== $attribute) {
            return false;
        }

        if (!$user->isEtatCivilComplet()) {
            return false;
        }

        return true;
    }
}
