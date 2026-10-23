<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Depouillement;
use App\Entity\DepouillementPhoto;
use App\Entity\User;
use App\Enum\Role;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Droits sur les photos d'une enveloppe.
 *
 * - MANAGE : voir, supprimer et partager. Scénaristes, orgas, admins, et dépouilleurs du dépouillement (actuel ou passé).
 * - VIEW : tout cela, plus le propriétaire de l'enveloppe (joueur, ou chef de groupe) quand la photo lui est partagée.
 */
class DepouillementPhotoVoter extends Voter
{
    public const string VIEW = 'DEPOUILLEMENT_PHOTO_VIEW';

    public const string MANAGE = 'DEPOUILLEMENT_PHOTO_MANAGE';

    public function __construct(
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::MANAGE], strict: true) && $subject instanceof DepouillementPhoto;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, #[\SensitiveParameter] TokenInterface $token, ?Vote $vote = null): bool
    {
        $utilisateur = $token->getUser();
        if (!$utilisateur instanceof User || !$subject instanceof DepouillementPhoto) {
            return false;
        }

        if ($this->peutGerer($utilisateur, $subject->getDepouillement())) {
            return true;
        }

        return self::VIEW === $attribute && $subject->isPartage() && $this->estProprietaire($utilisateur, $subject->getDepouillement());
    }

    private function peutGerer(User $utilisateur, Depouillement $depouillement): bool
    {
        foreach ([Role::ADMIN, Role::ORGA, Role::SCENARISTE] as $role) {
            if ($this->security->isGranted($role->value)) {
                return true;
            }
        }

        if ($depouillement->getDepouilleur()?->getId() === $utilisateur->getId()) {
            return true;
        }

        foreach ($depouillement->getHistories() as $histoire) {
            if ($histoire->getUser()?->getId() === $utilisateur->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Propriétaire de l'enveloppe : le joueur (enveloppe de joueur) ou le chef de groupe (enveloppe de groupe).
     */
    private function estProprietaire(User $utilisateur, Depouillement $depouillement): bool
    {
        $proprietaire = $depouillement->getParticipant()?->getUser() ?? $depouillement->getGroupeGn()?->getResponsable()?->getUser();

        return $proprietaire?->getId() === $utilisateur->getId();
    }
}
