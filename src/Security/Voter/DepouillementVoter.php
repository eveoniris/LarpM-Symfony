<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Conception;
use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Entity\User;
use App\Enum\Role;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Droits sur un dépouillement, une conception d'héritier ou le GN.
 *
 * - VALIDER : la validation (et le renvoi, la décision sur les héritiers) est réservée aux scénaristes, wargames et admins.
 * - VOIR et SAISIR (sur un dépouillement) : une simulation reste ouverte à tout bêta-testeur. Un dossier RÉEL est ouvert aux
 *   scénaristes, wargames et admins, et à son joueur propriétaire (le participant dont le numéro est celui du dossier). Le joueur
 *   saisit tant qu'aucun autre n'a pris le relais : dès qu'un tiers a repris le dossier, le joueur n'a plus que la lecture.
 *   Le statut du dossier (modifiable ou non) est vérifié à part, par le dépouillement lui-même.
 */
class DepouillementVoter extends Voter
{
    public const string VALIDER = 'DEPOUILLEMENT_VALIDER';

    public const string VOIR = 'DEPOUILLEMENT_VOIR';

    public const string SAISIR = 'DEPOUILLEMENT_SAISIR';

    public function __construct(
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (\in_array($attribute, [self::VOIR, self::SAISIR], strict: true)) {
            return $subject instanceof Depouillement;
        }

        return self::VALIDER === $attribute && ($subject instanceof Depouillement || $subject instanceof Conception || $subject instanceof Gn);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, #[\SensitiveParameter] TokenInterface $token, ?Vote $vote = null): bool
    {
        if ($this->estStaff()) {
            return true;
        }

        if (self::VALIDER === $attribute || !$subject instanceof Depouillement) {
            return false;
        }

        if ($subject->isSimulation()) {
            return true;
        }

        $utilisateur = $token->getUser();
        if (!$utilisateur instanceof User || !$this->estProprietaire($utilisateur, $subject)) {
            return false;
        }

        return self::VOIR === $attribute || $this->aLaMain($utilisateur, $subject);
    }

    private function estStaff(): bool
    {
        foreach ([Role::SCENARISTE, Role::WARGAME, Role::ADMIN] as $role) {
            if ($this->security->isGranted($role->value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Le joueur propriétaire d'un dossier de joueur : l'utilisateur du participant concerné. Un dossier de groupe n'a pas de propriétaire.
     */
    private function estProprietaire(User $utilisateur, Depouillement $depouillement): bool
    {
        return $depouillement->getParticipant()?->getUser()?->getId() === $utilisateur->getId();
    }

    /**
     * Le joueur garde la main tant que le dossier est le sien ou n'a personne : un tiers qui le reprend (ou le valide) la lui retire.
     */
    private function aLaMain(User $utilisateur, Depouillement $depouillement): bool
    {
        $saisisseur = $depouillement->getDepouilleur();

        return !$saisisseur instanceof User || $saisisseur->getId() === $utilisateur->getId();
    }
}
