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
     * Droit d'écriture général sur un territoire existant (fiche, événements, sanctuaire,
     * suppression...) : accordé globalement à Organisateur/Cohérence, ou ciblé au scénariste
     * du groupe propriétaire du territoire, sur ce territoire uniquement.
     */
    public const string EDIT = 'TERRITOIRE_EDIT';

    /**
     * Droit de modifier les langues (principale et parlées) d'un territoire.
     * Même logique que EDIT (alias historique conservé pour la route dédiée).
     */
    public const string EDIT_LANGUE = 'TERRITOIRE_EDIT_LANGUE';

    public function __construct(
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::EDIT, self::EDIT_LANGUE], strict: true) && $subject instanceof Territoire;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        // Les organisateurs et le rôle Cohérence ont un accès global (pas les cartographes/
        // scénaristes simples, qui n'ont d'écriture que sur leur propre territoire).
        if ($this->security->isGranted(Role::ORGA->value) || $this->security->isGranted(Role::COHERENCE->value)) {
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
