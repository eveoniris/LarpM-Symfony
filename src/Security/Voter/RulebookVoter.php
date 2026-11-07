<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\RulebookRegle;
use App\Entity\User;
use App\Enum\Role;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Droits sur le rulebook.
 *
 * - ACCESS (sans sujet) : entrer dans le rulebook. Scénaristes, équipe règle, admins.
 * - VIEW (sur une règle) : version claire. Équipe règle : toutes les règles. Scénaristes : seulement les règles visibles
 *   d'eux (publiées, statut public, version claire rédigée).
 * - VIEW_TECHNIQUE : détail technique, statuts non publics, brouillons. Équipe règle uniquement.
 * - EDIT : créer, modifier, restaurer une règle. Équipe règle uniquement.
 * - MANAGE_DOMAINES (sans sujet) : créer, renommer, réordonner les domaines. Admin uniquement.
 *
 * ROLE_ADMIN hérite de ROLE_REGLE et ROLE_SCENARISTE (security.yaml).
 */
class RulebookVoter extends Voter
{
    public const string ACCESS = 'RULEBOOK_ACCESS';

    public const string VIEW = 'RULEBOOK_VIEW';

    public const string VIEW_TECHNIQUE = 'RULEBOOK_VIEW_TECHNIQUE';

    public const string EDIT = 'RULEBOOK_EDIT';

    public const string MANAGE_DOMAINES = 'RULEBOOK_MANAGE_DOMAINES';

    public function __construct(
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (\in_array($attribute, [self::ACCESS, self::MANAGE_DOMAINES], strict: true)) {
            return true;
        }

        return \in_array($attribute, [self::VIEW, self::VIEW_TECHNIQUE, self::EDIT], strict: true) && $subject instanceof RulebookRegle;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, #[\SensitiveParameter] TokenInterface $token, ?Vote $vote = null): bool
    {
        if (!$token->getUser() instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::MANAGE_DOMAINES => $this->security->isGranted(Role::ADMIN->value),
            self::ACCESS => $this->estEquipeRegle() || $this->security->isGranted(Role::SCENARISTE->value),
            self::VIEW => $this->estEquipeRegle() || $subject instanceof RulebookRegle && $subject->isVisibleScenariste() && $this->security->isGranted(Role::SCENARISTE->value),
            self::VIEW_TECHNIQUE, self::EDIT => $this->estEquipeRegle(),
            default => false,
        };
    }

    private function estEquipeRegle(): bool
    {
        return $this->security->isGranted(Role::REGLE->value);
    }
}
