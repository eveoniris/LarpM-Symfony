<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Territoire;
use App\Entity\User;
use App\Enum\Role;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Autorisations par instance sur un territoire.
 */
class TerritoireVoter extends Voter
{
    /**
     * Droit de modifier les langues (principale et parlées) d'un territoire.
     */
    public const string EDIT_LANGUE = 'TERRITOIRE_EDIT_LANGUE';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::EDIT_LANGUE === $attribute && $subject instanceof Territoire;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        // Les organisateurs et cartographes conservent leur accès existant
        // (ROLE_SCENARISTE global hérite de ROLE_CARTOGRAPHE dans security.yaml).
        if ($this->security->isGranted(Role::ORGA->value) || $this->security->isGranted(Role::CARTOGRAPHE->value)) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Territoire $subject */
        $scenariste = $subject->getGroupe()?->getScenariste();

        return null !== $scenariste && null !== $user->getId() && $scenariste->getId() === $user->getId();
    }
}
