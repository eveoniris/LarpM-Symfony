<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Conception;
use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Entity\User;
use App\Enum\DepouillementType;
use App\Enum\Role;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Droits sur un dépouillement, une conception d'héritier ou le GN.
 *
 * - VALIDER : la validation (et le renvoi, la décision sur les héritiers) est réservée aux scénaristes, wargames et admins.
 * - CONTENU : saisir le contenu physique de l'enveloppe, réservé au staff (les simulations restent ouvertes aux bêta-testeurs).
 * - VOIR et SAISIR (sur un dépouillement) : une simulation reste ouverte à tout bêta-testeur. Un dossier RÉEL est ouvert aux
 *   scénaristes, wargames et admins, et à son joueur propriétaire (le participant dont le numéro est celui du dossier). Le joueur
 *   saisit tant qu'aucun autre n'a pris le relais : dès qu'un tiers a repris le dossier, le joueur n'a plus que la lecture.
 *   Le statut du dossier (modifiable ou non) est vérifié à part, par le dépouillement lui-même.
 */
// @mago-expect lint:cyclomatic-complexity
class DepouillementVoter extends Voter
{
    public const string VALIDER = 'DEPOUILLEMENT_VALIDER';

    public const string VOIR = 'DEPOUILLEMENT_VOIR';

    public const string SAISIR = 'DEPOUILLEMENT_SAISIR';

    public const string CONTENU = 'DEPOUILLEMENT_CONTENU';

    public function __construct(
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (\in_array($attribute, [self::VOIR, self::SAISIR, self::CONTENU], strict: true)) {
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

        // Le contenu physique de l'enveloppe (photos, étiquettes, contenu, actions) n'est jamais écrit par le joueur d'un dossier réel.
        if (self::CONTENU === $attribute) {
            return false;
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
     * Le joueur propriétaire d'un dossier de joueur : l'utilisateur du participant concerné. Pour un dossier de groupe : le chef du
     * groupe et les joueurs de ses six titres (suzerain, chef de guerre, diplomate, intendant, navigateur, éminence grise).
     */
    private function estProprietaire(User $utilisateur, Depouillement $depouillement): bool
    {
        if (DepouillementType::GROUPE === $depouillement->getType()) {
            return $depouillement->getGroupeGn()?->peutSaisirRetour($utilisateur) ?? false;
        }

        return $depouillement->getParticipant()?->getUser()?->getId() === $utilisateur->getId();
    }

    /**
     * Le joueur garde la main tant que le dossier est le sien ou n'a personne : un tiers qui le reprend (ou le valide) la lui retire.
     */
    private function aLaMain(User $utilisateur, Depouillement $depouillement): bool
    {
        $saisisseur = $depouillement->getDepouilleur();

        // Un retour de groupe est partagé : il reste à tous ses titulaires tant que personne d'autre (le staff) n'en a pris le relais.
        if (DepouillementType::GROUPE === $depouillement->getType() && $saisisseur instanceof User) {
            return $saisisseur->getId() === $utilisateur->getId() || ($depouillement->getGroupeGn()?->peutSaisirRetour($saisisseur) ?? false);
        }

        return !$saisisseur instanceof User || $saisisseur->getId() === $utilisateur->getId();
    }
}
