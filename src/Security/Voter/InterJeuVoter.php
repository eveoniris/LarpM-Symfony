<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\InterJeu;
use App\Entity\User;
use App\Enum\Role;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Droits sur un inter-jeu.
 *
 * - VIEW : consulter la liste des participants et les statistiques. Rôle INTER_JEU (ou admin),
 *   ou orga externe dont l'accès à cet inter-jeu a été validé.
 * - PROPOSE : proposer un ajout ou un changement de statut. Orga externe validé uniquement
 *   (le rôle INTER_JEU écrit directement).
 * - MANAGE : écrire (participants, statuts, validation des propositions et des accès). Rôle INTER_JEU ou admin.
 */
class InterJeuVoter extends Voter
{
    public const string VIEW = 'INTER_JEU_VIEW';

    public const string PROPOSE = 'INTER_JEU_PROPOSE';

    public const string MANAGE = 'INTER_JEU_MANAGE';

    public function __construct(
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::PROPOSE, self::MANAGE], strict: true) && $subject instanceof InterJeu;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, #[\SensitiveParameter] TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User || !$subject instanceof InterJeu) {
            return false;
        }

        $gestionnaire = $this->security->isGranted(Role::INTER_JEU->value) || $this->security->isGranted(Role::ADMIN->value);

        return match ($attribute) {
            self::MANAGE => $gestionnaire,
            self::VIEW => $gestionnaire || null !== $subject->getOrga($user),
            self::PROPOSE => !$gestionnaire && null !== $subject->getOrga($user),
            default => false,
        };
    }
}
